<?php
$path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

switch ($path) {
    case '':
    case 'dashboard':
        require 'dashboard.php'; break;
    case 'events':
        require 'events.php'; break;
    case 'login':
        require 'login.php'; break;
    case 'logout':
        require 'logout.php'; break;
    case 'guest_portal':
        require 'guest_portal.php'; break;
    case 'guests':
        require 'guests.php'; break;
    case 'up':
        require 'uploads.php'; break;
    case 'news_admin':
        require 'news_admin.php'; break;
    case 'news_edit':
        require 'news_edit.php'; break;
    case 'find_event':
        require 'find_event.php'; break;
        case 'miskus':
        require 'info.php'; break;
    default:
        // handled below
        break;
}

if (preg_match('#^e/([A-Za-z0-9]+)$#', $path, $m)) {
    $_GET['public_id'] = $m[1];
    require 'event_public.php';
    return;
}

if (preg_match('#^news/([A-Za-z0-9]+)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    require 'news_view.php';
    return;
}

if ($path !== '' && !in_array($path, ['dashboard','events','login','logout','guest_portal','guests','find_event','news_admin','news_edit'])) {
    http_response_code(404);
    echo "404 Not Found";
}
