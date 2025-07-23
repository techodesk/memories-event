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
    default:
        http_response_code(404);
        echo "404 Not Found";
}
