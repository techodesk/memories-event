<?php
require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$publicId = $_GET['public_id'] ?? '';
if ($publicId === '') {
    http_response_code(404);
    echo 'Public ID missing';
    exit;
}

$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . '/e/' . $publicId;

$qrCode = QrCode::create($url)
    ->setSize(300)
    ->setMargin(10);

$writer = new PngWriter();
$result = $writer->write($qrCode);

header('Content-Type: ' . $result->getMimeType());

echo $result->getString();

