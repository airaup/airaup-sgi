<?php
include("config.php");
require 'class.phpmailer.php';
require 'class.smtp.php';

function sendMail($email_to, $subject, $body)
{
    if (!$email_to) {
        return;
    }

    global $config;

    $mail = new PHPMailer();
    $mail->CharSet = 'UTF-8';
    $mail->IsSMTP();

    $mail->Host = $config['SMTP_HOST'];
    $mail->Port = $config['SMTP_PORT'];
    $mail->SMTPAuth = (bool) $config['SMTP_AUTH'];
    $mail->Username = $config['SMTP_USERNAME'];
    $mail->Password = $config['SMTP_PASSWORD'];
    $mail->FromName = $config['SMTP_FROMNAME'];
    $mail->From = $config['SMTP_FROM'];

    if ((bool) $config['SMTP_SECURE']) {
        $mail->SMTPSecure = $config['SMTP_SECURE'];
    }

    $mail->Subject = $subject;
    $mail->MsgHTML($body);
    $mail->AddAddress($email_to);
    $mail->Send();
}
