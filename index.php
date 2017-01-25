<?php

// only allow ajax request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    http_response_code(500);
    die();
}

date_default_timezone_set('Asia/Kuala_Lumpur');

$config = include ('config.php');
require 'vendor/autoload.php';

$mail = new PHPMailer;
$mail->isSMTP();

if (isset($config['debug']) && $config['debug']) {
    $mail->SMTPDebug = $config['debug'];
    $mail->Debugoutput = 'html';
}

// allowed SMTP hosts
$smtpHosts = [
    'gmail' => 'smtp.gmail.com',
    'outlook' => 'smtp.live.com'
];

// set gmail as default host driver
$mail->Host = $smtpHosts['gmail'];

if (!array_key_exists($config['driver'], $smtpHosts)) throw new Error('Invalid driver!');
if (isset($config['driver']) && $config['driver']) $mail->Host = $smtpHosts[$config['driver']];

$mail->Port = 587;
$mail->SMTPSecure = 'tls';
$mail->SMTPAuth = true;

if (!isset($config['name']) || !$config['name']) throw new Error('Missing name!');
if (!isset($config['email']) || !$config['email']) throw new Error('Missing email!');
if (!isset($config['password']) || !$config['password']) throw new Error('Missing password!');

$mail->Username = $config['email'];
$mail->Password = $config['password'];
$mail->setFrom($config['email'], $config['name']);
$mail->addAddress($config['email'], $config['name']);

$subject = null;
$body = null;
$altBody = null;

if (!isset($_POST['subject']) || !$_POST['subject']) throw new Error('Invalid request! Missing mail subject!');
if (!isset($_POST['body']) || !$_POST['body']) throw new Error('Invalid request! Missing mail body!');

$subject = $_POST['subject'];
$body = $_POST['body'];

if (!isset($_POST['altBody']) || !$_POST['altBody']) {
    $altBody = strip_tags($body);
} else {
    $altBody = $_POST['altBody'];
}

if (!$subject || !$body) {
    throw new Error('Invalid request!');
}

/**
 * Set mail subject
 */
$mail->Subject = $subject;

/**
 * Set mail body and alternate body
 * Example: $mail->msgHTML(file_get_contents('contents.html'), dirname(__FILE__));
 */
$mail->msgHTML($body);
$mail->AltBody = $altBody;

/**
 * Attach file to mail
 */
// $mail->addAttachment('images/phpmailer_mini.png');

$response = [];
$reponseCode = 200;

$response['status'] = true;
$response['subject'] = $mail->Subject;
$response['body'] = $mail->Body;
$response['altBody'] = $mail->AltBody;
$response['error'] = null;

if (!$mail->send()) {

    if ($config['debug']) {
        $response['error'] = $mail->ErrorInfo;
        $reponseCode = 400;
        $response['status'] = false;
    }
}

http_response_code($reponseCode);
header('Content-Type: application/json');
echo json_encode($response);