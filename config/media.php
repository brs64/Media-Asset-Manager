<?php

return [
    'ffmpeg_path' => env('FFMPEG_PATH', '/usr/bin/ffmpeg'),

    'thumbnails' => [
        'width' => env('THUMBNAIL_WIDTH', 320),
        'suffix' => '_miniature.jpg',
        'storage_path' => 'thumbnails',
        'quality' => 3,
    ],

    'nas' => [
        'archive' => env('NAS_ARCHIVE_PATH', '/mnt/nas/archive'),
        'pad' => env('NAS_PAD_PATH', '/mnt/nas/pad'),
        'mpeg' => env('NAS_MPEG_PATH', '/mnt/nas/mpeg'),
    ],
];
