<?php
require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Exceptions\MailerSendHttpException;

// Optional token protection — set in URL like ?to=example@domain.com&token=secret123
$token = $_GET['token'] ?? '';
$expectedToken = 'secret123'; // <- Change this or remove check below if not needed

if ($token !== $expectedToken) {
    http_response_code(403);
    echo '❌ Forbidden. Missing or invalid token.';
    exit;
}

// Get recipient email from ?to=
$to = $_GET['to'] ?? '';
if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo '❌ Invalid or missing recipient email. Usage: ?to=you@example.com';
    exit;
}

$msConf = $config['mailersend'];

try {
    $mailersend = new MailerSend(['api_key' => $msConf['api_key']]);

    $emailParams = (new EmailParams())
        ->setFrom($msConf['from_email'])
        ->setFromName($msConf['from_name'])
        ->setRecipients([new Recipient($to, 'Test User')])
        ->setSubject('MailerSend API Test')
        ->setText('This is a test email sent from a PHP browser script.')
        ->setHtml('<p>This is a <strong>test email</strong> sent from a PHP browser script.</p>');

    $mailersend->email->send($emailParams);

    echo "<p>✅ <strong>Success!</strong> Test email sent to <code>$to</code>.</p>";
} catch (MailerSendHttpException $e) {
    $response = $e->getMessage();

    // Safely attempt to get API response body if available
    try {
        $raw = $e->getResponse();
        if ($raw && method_exists($raw, 'getBody')) {
            $response = $raw->getBody()->getContents();
        }
    } catch (Throwable $t) {
        // Do nothing – fallback already used
    }

    echo "<p>❌ <strong>MailerSend API error:</strong></p><pre>" . htmlspecialchars($response) . "</pre>";
} catch (Exception $e) {
    echo "<p>❌ <strong>General error:</strong></p><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
