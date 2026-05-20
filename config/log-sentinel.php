<?php

return [
    'enabled' => env('LOG_SENTINEL_ENABLED', true),

    'route_prefix' => env('LOG_SENTINEL_ROUTE_PREFIX', 'admin/log-sentinel'),

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | For local development, ['web'] is fine.
    | For production, publish this config and change it to ['web', 'auth']
    | or your own admin middleware.
    |
    */
    'middleware' => ['web'],

    'package_name' => 'Laravel Log Sentinel',

    'source_types' => [
        'laravel' => 'Laravel Log',
        'apache_access' => 'Apache Access Log',
        'apache_error' => 'Apache Error Log',
        'nginx_access' => 'Nginx Access Log',
        'nginx_error' => 'Nginx Error Log',
        'mysql_error' => 'MySQL Error Log',
        'postgresql' => 'PostgreSQL Log',
        'ssh_auth' => 'SSH / Auth Log',
        'linux_system' => 'Linux System Log',
        'generic' => 'Generic Log File',
    ],

    'active_parsers' => [
        'laravel',
        'apache_access',
        'nginx_access',
        'apache_error',
        'nginx_error',
        'ssh_auth',
        'linux_system',
        'mysql_error',
        'postgresql',
    ],

    'path_security' => [
        'allow_only_configured_paths' => true,

        'allowed_base_paths' => [
            storage_path('logs'),
            // Linux common log locations
            '/var/log',
            '/var/log/apache2',
            '/var/log/httpd',
            '/var/log/nginx',
            '/var/log/mysql',
            '/var/log/postgresql',
        ],

        'block_path_traversal' => true,
        'block_stream_wrappers' => true,
    ],

    'redaction' => [
        'enabled' => true,

        'mask' => '[REDACTED]',

        'mask_emails' => false,

        'sensitive_keys' => [
            'password',
            'passwd',
            'pwd',
            'token',
            'api_key',
            'apikey',
            'secret',
            'authorization',
            'bearer',
            'session',
            'cookie',
            'csrf',
        ],
    ],

    'retention' => [
        'default_log_days' => 30,
        'resolved_alert_days' => 90,
        'keep_entries_with_open_alerts' => true,
        'prune_chunk_size' => 500,
    ],
];
