<?php
$local = is_file(__DIR__ . '/local.php') ? require __DIR__ . '/local.php' : [];
$db = $local['db'] ?? ['class' => yii\db\Connection::class, 'dsn' => 'sqlite:' . (getenv('KPI_RUNTIME_DIR') ?: dirname(__DIR__) . '/runtime') . '/kpi.sqlite', 'charset' => 'utf8'];
if(str_starts_with($db['dsn'],'sqlite:'))$db['on afterOpen']=static function($event){$event->sender->createCommand('PRAGMA foreign_keys=ON')->execute();$event->sender->createCommand('PRAGMA busy_timeout=10000')->execute();};
return [
    'id' => 'kpi', 'name' => 'KPI · Universitet', 'basePath' => dirname(__DIR__),
    'language' => 'uz-Latn', 'timeZone' => 'Asia/Tashkent',
    'runtimePath' => getenv('KPI_RUNTIME_DIR') ?: dirname(__DIR__) . '/runtime',
    'components' => [
        'db' => $db,
        'cache' => ['class' => yii\caching\FileCache::class],
        'log' => ['targets' => [['class' => yii\log\FileTarget::class, 'levels' => ['error', 'warning'], 'logVars' => []]]],
    ],
    'params' => ['cookieKey' => $local['cookieKey'] ?? '', 'uploadLimit' => 10 * 1024 * 1024],
];
