<?php
if(!getenv('KPI_RUNTIME_DIR') || !str_contains(getenv('KPI_RUNTIME_DIR'),'/http-test-'))exit(1);
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';Yii::setAlias('@app',dirname(__DIR__));new yii\console\Application(require dirname(__DIR__).'/config/console.php');
foreach(['operator'=>'admin','unassigned'=>'unassigned'] as $name=>$role){$u=app\models\User::findOne(['username'=>'http_'.$name])??new app\models\User();$u->username='http_'.$name;$u->name='HTTP '.$name;$u->role=$role;$u->category='PHD';$u->active=1;$u->must_change_password=0;$u->created_at=time();$u->setPassword('OnlyForIsolated-HttpTests2026!');$u->save(false);}
$u=app\models\User::findOne(['username'=>'http_teacher']);$u->hemis_profile=json_encode(['photo_url'=>'https://student.fdu.uz/static/test-photo.png','email'=>'teacher@example.test','academic_rank'=>'Dotsent']);$u->save(false);app\services\HemisPhoto::store((int)$u->id,'https://student.fdu.uz/static/test-photo.png',file_get_contents(dirname(__DIR__).'/web/images/fardu-logo.png'));
echo "Organization fixtures ready.\n";
