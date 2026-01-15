<?php

declare(strict_types=1);

return [
    'app' => [
        'name' => 'Pamikil Learning',
        'debug' => true,
        'timezone' => 'UTC',
        'base_url' => '',
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'pamikil',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'pamikil_session',
        'timeout_seconds' => 60 * 30,
        'regenerate_seconds' => 60 * 10,
        'cookie_samesite' => 'Lax',
    ],
    'security' => [
        'bcrypt_cost' => 12,
    ],
];
