<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        'app' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        'ftp_pad' => [
            'driver' => 'ftp',
            'host' => env('FTP_PAD_HOST', 'btsplay-ftp-pad'),
            'username' => env('FTP_PAD_USER', 'ftpuser'),
            'password' => env('FTP_PAD_PASS', 'ftppass'),
            'port' => (int) env('FTP_PAD_PORT', 21),
            'root' => env('FTP_PAD_ROOT') ?: '',
            'passive' => true,
            'ssl' => false,
            'timeout' => 30,
        ],

        'ftp_mpeg' => [
            'driver' => 'ftp',
            'host' => env('FTP_MPEG_HOST', 'btsplay-ftp-mpeg'),
            'username' => env('FTP_MPEG_USER', 'ftpuser'),
            'password' => env('FTP_MPEG_PASS', 'ftppass'),
            'port' => (int) env('FTP_MPEG_PORT', 21),
            'root' => env('FTP_MPEG_ROOT') ?: '',
            'passive' => true,
            'ssl' => false,
            'timeout' => 30,
        ],

        'ftp_arch' => [
            'driver' => 'ftp',
            'host' => env('FTP_ARCH_HOST', 'btsplay-ftp-arch'),
            'username' => env('FTP_ARCH_USER', 'ftpuser'),
            'password' => env('FTP_ARCH_PASS', 'ftppass'),
            'port' => (int) env('FTP_ARCH_PORT', 21),
            'root' => env('FTP_ARCH_ROOT') ?: '',
            'passive' => true,
            'ssl' => false,
            'timeout' => 30,
        ],

        'external_local' => [
        'driver' => 'local',
        'root' => env('FILESYSTEM_LOCAL_PATH'),
        'visibility' => 'private', // Or public, depending on needs
        'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
