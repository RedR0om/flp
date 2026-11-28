<?php

namespace App\Support;

final class Roles
{
    public const DIVISION_ADMIN = 'division_admin';

    public const PSDS = 'psds';

    public const SCHOOL_HEAD = 'school_head';

    public const FLP_FOCAL = 'flp_focal';

    public const OIC = 'oic';

    public const SDS = 'sds';

    /**
     * Edit reports, validations, and actions for one cluster.
     * School head, FLP focal, and OIC use the same access as a PSDS.
     *
     * @var list<string>
     */
    public const CLUSTER_EDITORS = [
        self::PSDS,
        self::SCHOOL_HEAD,
        self::FLP_FOCAL,
        self::OIC,
    ];

    /**
     * Roles that can edit a school report inside their scope.
     * SDS is view-only and is intentionally absent.
     *
     * @var list<string>
     */
    public const EDITORS = [
        self::DIVISION_ADMIN,
        self::PSDS,
        self::SCHOOL_HEAD,
        self::FLP_FOCAL,
        self::OIC,
    ];

    /**
     * Roles a division administrator can assign. The remaining titles
     * stay out until their access is defined.
     *
     * @var list<string>
     */
    public const ASSIGNABLE = [
        self::DIVISION_ADMIN,
        self::SDS,
        self::PSDS,
        self::SCHOOL_HEAD,
        self::FLP_FOCAL,
        self::OIC,
    ];

    public static function label(string $role): string
    {
        return match ($role) {
            self::DIVISION_ADMIN => 'Division administrator',
            self::SDS => 'SDS',
            self::PSDS => 'PSDS',
            self::SCHOOL_HEAD => 'School head',
            self::FLP_FOCAL => 'FLP focal',
            self::OIC => 'OIC',
            default => $role,
        };
    }

    /**
     * Finished roles, plus titles still waiting for an access rule:
     * SDO personnel, EPS, teacher, authorized validator, and ICT.
     *
     * @var list<string>
     */
    public const ALL = [
        self::DIVISION_ADMIN,
        self::PSDS,
        self::SCHOOL_HEAD,
        self::FLP_FOCAL,
        self::OIC,
        self::SDS,
        'sdo_personnel',
        'eps',
        'teacher',
        'authorized_validator',
        'ict',
    ];
}
