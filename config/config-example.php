<?php
return [
    // --- Database Connections ---
    'db_event_manager' => [
        'host'     => 'wedding_db',
        'dbname'   => 'event-manager',
        'user'     => 'event-manager',
        'pass'     => 'HIDDEN',
        'charset'  => 'utf8mb4',
    ],
    'db_memories' => [
        'host'     => 'wedding_db',
        'dbname'   => 'memories',
        'user'     => 'memories',
        'pass'     => 'HIDDEN',
        'charset'  => 'utf8mb4',
    ],

    // --- DigitalOcean Spaces ---
    'do_spaces' => [
        'key'       => 'HIDDEN',
        'secret'    => 'HIDDEN',
        'endpoint'  => 'https://ams3.digitaloceanspaces.com', // example: fra1
        'region'    => 'ams3',
        'bucket'    => 'HIDDEN',
        // Optional: 'uploads/' or leave blank for root
        'folder'    => 'uploads/',
        // e.g. 'https://your-bucket-name.fra1.digitaloceanspaces.com'
        'cdn_url'   => 'https://HIDDEN.ams3.cdn.digitaloceanspaces.com',
    ],

    // --- SMTP Settings ---
    'smtp' => [
        'host'       => 'smtp.office365.com',
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => 'user@example.com', // account with send-as rights
        'password'   => 'HIDDEN',
        'from_email' => 'shared@example.com',
        'from_name'  => 'Memories Team',
    ],

    // --- MailerSend Settings ---
    'mailersend' => [
        'api_key'    => 'mlsn.631ec23c7bfd4e3e5e7649cfe899d728e8ccfcf6a54c4df8d0807b2804cd9cbf',
        'from_email' => 'MS_AmGPKC@kaljusaar.vip',
        'from_name'  => 'Memories Team',
    ],
    // --- App Config (optional) ---
    'app' => [
        'env'       => 'development',
        'timezone'  => 'Europe/Tallinn',
    ],
];
