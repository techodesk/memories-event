<?php
require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';

use MailerSend\MailerSend;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\Exceptions\MailerSendHttpException;

$to = $argv[1] ?? null;
if (!$to) {
    echo "Usage: php test_mailersend.php recipient@example.com\n";
    exit(1);
}

$msConf = $config['mailersend'];

try {
    $mailersend = new MailerSend(['api_key' => $msConf['api_key']]);

    $emailParams = (new EmailParams())
        ->setFrom($msConf['from_email'])
        ->setFromName($msConf['from_name'])
        ->setRecipients([new Recipient($to, 'Test User')])
        ->setSubject('MailerSend API Test')
        ->setText('This is a test email from test_mailersend.php')
        ->setHtml('<p>This is a test email from test_mailersend.php</p>');

    $mailersend->email->send($emailParams);
    echo "Message sent successfully!\n";
} catch (MailerSendHttpException $e) {
    echo "MailerSend error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
