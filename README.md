![PHP Version](https://img.shields.io/badge/PHP-%5E8.2-blue)
![Laravel](https://img.shields.io/badge/Laravel-11%20%7C%2012%20%7C%2013-red)
![License](https://img.shields.io/badge/license-MIT-green)
![Status](https://img.shields.io/badge/status-active-success)
![Package](https://img.shields.io/badge/package-laravel--log--sentinel-black)
![Packagist Version](https://img.shields.io/packagist/v/osit/laravel-log-sentinel)
![Packagist Downloads](https://img.shields.io/packagist/dt/osit/laravel-log-sentinel)

## Tags

`laravel` `logs` `log-monitoring` `security-alerts` `admin-dashboard` `blade` `apache` `nginx` `mysql` `postgresql` `ssh` `linux` `datatables` `sweetalert2` `monitoring` `devops`

# Laravel Log Sentinel

Laravel Log Sentinel is a Laravel admin package for monitoring application, web server, database, SSH/authentication, and Linux system logs through a clean Blade dashboard.

It provides log source management, log parsing, searchable DataTables views, security alert detection, dashboard analytics, and retention/pruning tools.

## Features

- Blade-based admin dashboard
- Log source management
- Laravel log parser
- Apache access and error log parsers
- Nginx access and error log parsers
- SSH/auth log parser
- Linux system log parser
- MySQL error log parser
- PostgreSQL log parser
- DataTables log viewer
- DataTables alert viewer
- SweetAlert2 toast and confirmation notifications
- Security alert detection engine
- Dashboard analytics overview
- Log retention and pruning command
- Configurable path security
- Installer command

## Supported Log Sources

| Source | Parser Key |
|---|---|
| Laravel logs | `laravel` |
| Apache access logs | `apache_access` |
| Apache error logs | `apache_error` |
| Nginx access logs | `nginx_access` |
| Nginx error logs | `nginx_error` |
| SSH/auth logs | `ssh_auth` |
| Linux system logs | `linux_system` |
| MySQL error logs | `mysql_error` |
| PostgreSQL logs | `postgresql` |
| Generic/custom logs | `generic` |

## Requirements

- PHP 8.2+
- Laravel 11, 12, or 13
- MySQL, MariaDB, PostgreSQL, or another Laravel-supported database
- Composer

## Installation

Install the package with Composer:

```bash
composer require osit/laravel-log-sentinel
```

Run the installer:

```bash
php artisan log-sentinel:install
```

Run migrations:

```bash
php artisan migrate
```

Optionally create the default Laravel log source during installation:

```bash
php artisan log-sentinel:install --with-default-source
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=log-sentinel-config
```

The config file will be published to:

```text
config/log-sentinel.php
```

Important settings include:

```php
'enabled' => env('LOG_SENTINEL_ENABLED', true),

'route_prefix' => env('LOG_SENTINEL_ROUTE_PREFIX', 'admin/log-sentinel'),

'middleware' => ['web', 'auth'],

'path_security' => [
    'allow_only_configured_paths' => true,
    'allowed_base_paths' => [
        storage_path('logs'),
        '/var/log',
        '/var/log/apache2',
        '/var/log/nginx',
        '/var/log/mysql',
        '/var/log/postgresql',
    ],
],
```

## Admin Dashboard

After installation, open:

```text
/admin/log-sentinel
```

The dashboard includes:

- Total log sources
- Active log sources
- Total log entries
- Entries today
- Error entries
- Open alerts
- Critical alerts
- Activity overview
- Top alert types
- Top IP addresses
- Latest alerts
- Latest log entries

## Managing Log Sources

Open:

```text
/admin/log-sentinel/sources
```

From this page, admins can:

- Add log sources
- Edit log sources
- Enable or disable log sources
- Test file readability
- Scan individual sources
- Delete source records

Deleting a source does not delete the actual log file.

## Log Viewer

Open:

```text
/admin/log-sentinel/logs
```

The log viewer supports:

- Server-side DataTables loading
- Search
- Sorting
- Filtering by source
- Filtering by source type
- Filtering by level
- Filtering by HTTP status code
- Filtering by date range
- Viewing full log details

## Alert Viewer

Open:

```text
/admin/log-sentinel/alerts
```

The alert viewer supports:

- Server-side DataTables loading
- Search
- Filtering by severity
- Filtering by status
- Filtering by alert type
- Acknowledge alerts
- Resolve alerts
- Reopen alerts
- View linked log entries

## Security Alert Detection

Log Sentinel detects alerts for activity such as:

- `.env` access attempts
- `.git` access attempts
- phpMyAdmin scans
- WordPress admin scans
- HTTP 500 errors
- HTTP 403 responses
- Failed SSH logins
- Invalid SSH users
- Database authentication failures
- MySQL access denied errors
- PostgreSQL authentication failures
- Out-of-memory events
- Service failures
- Laravel critical errors
- Apache/Nginx permission denied events

Run detection against existing logs:

```bash
php artisan log-sentinel:detect-alerts --limit=5000
```

New alerts are automatically detected during log scans.

## Artisan Commands

### Install

```bash
php artisan log-sentinel:install
```

Options:

```bash
php artisan log-sentinel:install --force
php artisan log-sentinel:install --with-default-source
```

### Scan Logs

```bash
php artisan log-sentinel:scan
```

Scan one source only:

```bash
php artisan log-sentinel:scan --source_id=1
```

### Detect Alerts

```bash
php artisan log-sentinel:detect-alerts --limit=5000
```

### Prune Logs

Dry run:

```bash
php artisan log-sentinel:prune --dry-run
```

Run pruning:

```bash
php artisan log-sentinel:prune
```

Override retention days:

```bash
php artisan log-sentinel:prune --days=7 --dry-run
```

## Scheduler Setup

Add the commands to the host Laravel application scheduler.

In Laravel 11+ / 12, this is usually done in:

```text
routes/console.php
```

Example:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('log-sentinel:scan')->everyFiveMinutes();
Schedule::command('log-sentinel:prune')->dailyAt('02:00');
```

## Production Security Checklist

Before using this package in production:

- Use authentication middleware.
- Restrict access to admin users only.
- Keep path security enabled.
- Only allow trusted base paths.
- Do not allow arbitrary file browsing.
- Disable Laravel debug mode.
- Run pruning daily.
- Review retention settings.
- Protect server log file permissions.

Recommended middleware config:

```php
'middleware' => ['web', 'auth'],
```

For projects with custom admin middleware:

```php
'middleware' => ['web', 'auth', 'can:access-admin'],
```

or:

```php
'middleware' => ['web', 'auth', 'role:admin'],
```

## Path Security

Log Sentinel is designed to read log files only from configured base paths.

Example:

```php
'path_security' => [
    'allow_only_configured_paths' => true,

    'allowed_base_paths' => [
        storage_path('logs'),
        '/var/log',
        '/var/log/apache2',
        '/var/log/nginx',
        '/var/log/mysql',
        '/var/log/postgresql',
    ],
],
```

Avoid disabling path restrictions in production.

## Retention

Retention settings are configured in:

```php
'retention' => [
    'default_log_days' => 30,
    'resolved_alert_days' => 90,
    'keep_entries_with_open_alerts' => true,
    'prune_chunk_size' => 500,
],
```

Each log source can also define its own retention period.

## Publishing Views

To customize the Blade views:

```bash
php artisan vendor:publish --tag=log-sentinel-views
```

Views will be published to:

```text
resources/views/vendor/log-sentinel
```

## Publishing Migrations

```bash
php artisan vendor:publish --tag=log-sentinel-migrations
```

## Development Notes

During local package development, the package can be loaded through a Composer path repository:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/osit/log-sentinel",
        "options": {
            "symlink": true
        }
    }
]
```

Then require it in the host Laravel app:

```json
"osit/laravel-log-sentinel": "*@dev"
```

## License

MIT
