<?php

namespace App\Support;

final class Roles
{
    public const DIVISION_ADMIN = 'division_admin';

    public const PSDS = 'psds';

    public const SCHOOL_HEAD = 'school_head';

    public const FLP_FOCAL = 'flp_focal';

    /**
     * Roles that can edit a school report inside their scope.
     *
     * @var list<string>
     */
    public const EDITORS = [
        self::DIVISION_ADMIN,
        self::PSDS,
        self::SCHOOL_HEAD,
        self::FLP_FOCAL,
    ];

    /**
     * Working roles for this build, plus the remaining titles from the
     * role matrix so they exist before those screens are added.
     *
     * @var list<string>
     */
    public const ALL = [
        self::DIVISION_ADMIN,
        self::PSDS,
        self::SCHOOL_HEAD,
        self::FLP_FOCAL,
        'sds',
        'sdo_personnel',
        'oic',
        'eps',
        'teacher',
        'authorized_validator',
        'ict',
    ];
}
