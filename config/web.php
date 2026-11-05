<?php
$config = require __DIR__ . '/common.php';
$config['components'] += [
    'request' => ['cookieValidationKey' => $config['params']['cookieKey'], 'enableCsrfValidation' => true],
    'user' => ['identityClass' => app\models\User::class, 'enableAutoLogin' => false, 'loginUrl' => ['auth/login'], 'authTimeout' => 7200],
    'session' => ['class' => yii\web\Session::class, 'cookieParams' => ['httpOnly' => true, 'sameSite' => 'Lax']],
    'errorHandler' => ['errorAction' => 'auth/error'],
];
$config['defaultRoute'] = 'kpi/index';
return $config;
