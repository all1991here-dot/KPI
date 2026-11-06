<?php
if(!getenv('KPI_RUNTIME_DIR') || !str_contains(getenv('KPI_RUNTIME_DIR'),'/http-test-'))exit(1);
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';Yii::setAlias('@app',dirname(__DIR__));new yii\console\Application(require dirname(__DIR__).'/config/console.php');
$actor=app\models\User::findOne(['username'=>'http_admin']);if(!$actor || $actor->role!=='superadmin')exit(1);
if(($argv[1]??'')==='seed'){
    $u=app\models\User::findOne(['username'=>'hemis_recovery_fixture'])??new app\models\User();$u->deleted_at=null;$u->username='hemis_recovery_fixture';$u->name='Recovery employee fixture';$u->hemis_id='88001';$u->role='teacher';$u->category='PHD';$u->active=1;$u->must_change_password=0;$u->created_at=time();$u->setPassword('OnlyForIsolated-HttpTests2026!');$u->save(false);app\services\Management::deleteUser($actor,(int)$u->id);echo "Archived recovery fixture ready.\n";
}elseif(($argv[1]??'')==='restore'){
    $result=app\services\Hemis::applyEmployees([['id'=>88001,'full_name'=>'Recovery employee fixture']],true,$actor);$result['finished_at']=date('Y-m-d H:i:s');file_put_contents(Yii::getAlias('@runtime/hemis-import-last.json'),json_encode($result));echo "Recovered fixture: ".$result['restored']."\n";
}else exit(1);
