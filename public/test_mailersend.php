<?php
require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Exceptions\MailerSendHttpException;

// Get recipient from query parameter
$to = $_GET['to'] ?? null;
if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "❌ Usage: ?to=recipient@example.com";
    exit;
}

$msConf = $config['mailersend'];

try {
    $mailersend = new MailerSend(['api_key' => $msConf['api_key']]);

    $emailParams = (new EmailParams())
        ->setFrom($msConf['from_email'])
        ->setFromName($msConf['from_name'])
        ->setRecipients([new Recipient($to, 'Test User')])
        ->setSubject('MailerSend API Browser Test')
        ->setText('This is a test email sent from a PHP script in the browser.')
        ->setHtml('<p>This is a test email sent from a PHP script in the browser.</p>');

    $mailersend->email->send($emailParams);

    echo "✅ Message sent successfully to <strong>$to</strong>!";
} catch (MailerSendHttpException $e) {
    $response = method_exists($e, 'getResponse') && $e->getResponse()
        ? $e->getResponse()->getBody()->getContents()
        : $e->getMessage();

    echo "❌ MailerSend API error:<br><pre>$response</pre>";
} catch (Exception $e) {
    echo "❌ General error:<br><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
