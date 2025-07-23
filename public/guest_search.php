<?php
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/guests/GuestManager.php';

$memDbConf = $config['db_memories'];
$memPdo = new PDO("mysql:host={$memDbConf['host']};dbname={$memDbConf['dbname']};charset={$memDbConf['charset']}", $memDbConf['user'], $memDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$emDbConf = $config['db_event_manager'];
$emPdo = new PDO("mysql:host={$emDbConf['host']};dbname={$emDbConf['dbname']};charset={$emDbConf['charset']}", $emDbConf['user'], $emDbConf['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$manager = new GuestManager($emPdo, $memPdo);
$term = trim($_GET['q'] ?? '');
$guests = $term === '' ? $manager->fetchAllGuests() : $manager->searchGuests($term);

header('Content-Type: application/json');
echo json_encode($guests);
