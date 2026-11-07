<?php
$config = require __DIR__ . '/common.php';
// Temporarily use administrator-issued passwords; keep the change flow available for later.
$config['params']['passwordChangeEnabled'] = getenv('KPI_PASSWORD_CHANGE_ENABLED') === '1';
$config['components'] += [
    'request' => ['cookieValidationKey' => $config['params']['cookieKey'], 'enableCsrfValidation' => true],
    'user' => ['identityClass' => app\models\User::class, 'enableAutoLogin' => false, 'loginUrl' => ['auth/login'], 'authTimeout' => 7200],
    'session' => ['class' => yii\web\Session::class, 'cookieParams' => ['httpOnly' => true, 'sameSite' => 'Lax']],
    'errorHandler' => ['errorAction' => 'auth/error'],
];
$config['defaultRoute'] = 'kpi/index';
return $config;
