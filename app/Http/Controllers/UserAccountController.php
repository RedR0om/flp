<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveUserAccountRequest;
use App\Models\Cluster;
use App\Models\User;
use App\Support\ActivityLog;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserAccountController extends Controller
{
    public function index(Request $request): Response
    {
        $this->ensureAdmin($request);

        $accounts = User::query()
            ->with(['roles', 'cluster'])
            ->orderBy('name')
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->first()?->name,
                'role_label' => Roles::label((string) $user->roles->first()?->name),
                'cluster_id' => $user->cluster_id,
                'cluster_label' => $user->cluster ? 'Cluster '.$user->cluster->number : null,
            ]);

        return Inertia::render('Users', [
            'accounts' => $accounts,
            'roles' => collect(Roles::ASSIGNABLE)->map(fn (string $role) => [
                'value' => $role,
                'label' => Roles::label($role),
                'needs_cluster' => in_array($role, Roles::CLUSTER_EDITORS, true),
            ])->values(),
            'clusters' => Cluster::query()->orderBy('number')->get(['id', 'number']),
        ]);
    }

    public function store(SaveUserAccountRequest $request): RedirectResponse
    {
        $account = User::query()->create([
            'name' => $request->string('name')->trim()->toString(),
            'email' => $request->string('email')->trim()->toString(),
            'password' => $request->string('password')->toString(),
            'cluster_id' => $this->clusterId($request),
        ]);

        $account->markEmailAsVerified();

        $account->assignRole($request->string('role')->toString());

        ActivityLog::record($request->user(), null, $account->cluster_id, 'Account created', $account->email);

        return back();
    }

    public function update(SaveUserAccountRequest $request, User $user): RedirectResponse
    {
        $role = $request->string('role')->toString();

        if ($request->user()?->is($user) && $role !== Roles::DIVISION_ADMIN) {
            throw ValidationException::withMessages([
                'role' => 'You cannot change your own role.',
            ]);
        }

        $user->fill([
            'name' => $request->string('name')->trim()->toString(),
            'email' => $request->string('email')->trim()->toString(),
            'cluster_id' => $this->clusterId($request),
        ]);

        if ($request->filled('password')) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();
        $user->syncRoles([$role]);

        ActivityLog::record($request->user(), null, $user->cluster_id, 'Account updated', $user->email);

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_if($request->user()?->is($user), 403);

        $email = $user->email;
        $clusterId = $user->cluster_id;
        $user->delete();

        ActivityLog::record($request->user(), null, $clusterId, 'Account removed', $email);

        return back();
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user() instanceof User && $request->user()->hasRole(Roles::DIVISION_ADMIN), 403);
    }

    private function clusterId(SaveUserAccountRequest $request): ?int
    {
        if (! in_array($request->string('role')->toString(), Roles::CLUSTER_EDITORS, true)) {
            return null;
        }

        return $request->integer('cluster_id');
    }
}
