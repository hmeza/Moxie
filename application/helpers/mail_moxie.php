<?php
/**
 * This file should be put on a remote server with SMTP email enabled.
 * Must be called through GET with first parameter user email and second parameter
 * the key to access change password.
 */

$email = $_GET['m'];
$key = $_GET['k'];
$subject = 'Moxie - Restore password';
$body = 'Si recibes este email es o bien porque estás intentando restaurar tu contraseña. En tal caso,
por favor pulsa el siguiente link:

You are receiving this email because you want to restore your password. If so, please click
the following link:

http://moxie.redirectme.net/login/recoverpassword/k/'.$key.'

Moxie
http://moxie.redirectme.net
';
$headers = 'From: Moxie <hugo.meza@hytsolutions.com>' . "\r\n" .
'Reply-To: hugo.meza@hytsolutions.com' . "\r\n" .
'X-Mailer: PHP/' . phpversion();
mail($email, $subject, $body, $headers);
?>