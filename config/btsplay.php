<?php
// config/btsplay.php

return [
    'uris' => [
        'nas_pad'        => env('URI_RACINE_NAS_PAD'),
        'nas_arch'       => env('URI_RACINE_NAS_ARCH'),
        'local'          => env('URI_RACINE_STOCKAGE_LOCAL'),
        'nas_diff'       => env('URI_RACINE_NAS_DIFF'),
    ],
    'ftp' => [
        'pad' => [
            'ip'       => env('NAS_PAD_IP'),
            'user'     => env('NAS_PAD_USER'),
            'password' => env('NAS_PAD_PASSWORD'),
            'user_sup' => env('NAS_PAD_USER_SUP'),
            'pass_sup' => env('NAS_PAD_PASSWORD_SUP'),
        ],
        'arch' => [
            'ip'       => env('NAS_ARCH_IP'),
            'user'     => env('NAS_ARCH_USER'),
            'password' => env('NAS_ARCH_PASSWORD'),
            'user_sup' => env('NAS_ARCH_USER_SUP'),
            'pass_sup' => env('NAS_ARCH_PASSWORD_SUP'),
        ],
        'diff' => [
            'ip'       => env('NAS_DIFF_IP'),
            'user'     => env('NAS_DIFF_USER'),
            'password' => env('NAS_DIFF_PASSWORD'),
        ],
    ],
    'backup' => [
        'uri_generated'    => env('URI_FICHIER_GENERES'),
        'uri_dump'         => env('URI_DUMP_SAUVEGARDE'),
        'uri_constants'    => env('URI_CONSTANTES_SAUVEGARDE'),
        'suffix_dump'      => env('SUFFIXE_FICHIER_DUMP_SAUVEGARDE'),
        'suffix_constants' => env('SUFFIXE_FICHIER_CONSTANTES_SAUVEGARDE'),
    ],
    'logs' => [
        'general'       => env('NOM_FICHIER_LOG_GENERAL'),
        'backup'        => env('NOM_FICHIER_LOG_SAUVEGARDE'),
        'max_lines'     => env('NB_LIGNES_LOGS'),
        'recent_first'  => env('AFFICHAGE_LOGS_PLUS_RECENTS_PREMIERS'),
    ],
    'process' => [
        'max_transfer'      => env('NB_MAX_PROCESSUS_TRANSFERT'),
        'max_sub_transfer'  => env('NB_MAX_SOUS_PROCESSUS_TRANSFERT'),
    ],
    'display' => [
        'swiper_count'  => env('NB_VIDEOS_PAR_SWIPER'),
        'history_count' => env('NB_VIDEOS_HISTORIQUE_TRANSFERT'),
    ]
];