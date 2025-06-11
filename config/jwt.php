<?php

return [
    'secret' => env('JWT_SECRET', 'your-secure-default-secret'),

    'keys' => [
        'public' => 'file://' . base_path(env('JWT_PUBLIC_KEY')),
        'private' => 'file://' . base_path(env('JWT_PRIVATE_KEY')),
        'passphrase' => env('JWT_PASSPHRASE'),
    ],

    'ttl' => (int) env('JWT_TTL', 60),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 4320),
    'algo' => env('JWT_ALGO', 'RS256'),

    'required_claims' => [
        'iss', 'iat', 'exp', 'nbf', 'sub', 'jti',
    ],

    'persistent_claims' => [],

    'lock_subject' => true,

    'leeway' => (int) env('JWT_LEEWAY', 0),

    'blacklist_enabled' => env('JWT_BLACKLIST_ENABLED', true),
    'blacklist_grace_period' => (int) env('JWT_BLACKLIST_GRACE_PERIOD', 0),
    'show_black_list_exception' => env('JWT_SHOW_BLACKLIST_EXCEPTION', true),

    'decrypt_cookies' => false,
    'cookie_key_name' => 'token',

    'providers' => [
        'jwt' => PHPOpenSourceSaver\JWTAuth\Providers\JWT\Lcobucci::class,
        'auth' => PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate::class,
        'storage' => PHPOpenSourceSaver\JWTAuth\Providers\Storage\Illuminate::class,
    ],
];