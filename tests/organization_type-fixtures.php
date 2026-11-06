<?php
if(!getenv('KPI_RUNTIME_DIR') || !str_contains(getenv('KPI_RUNTIME_DIR'),'/http-test-'))exit(1);
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';Yii::setAlias('@app',dirname(__DIR__));new yii\console\Application(require dirname(__DIR__).'/config/console.php');
app\services\Hemis::applyStructure([
 ['id'=>91010,'name'=>'Chet tillari fakulteti','structureType'=>['code'=>'11','name'=>'Fakultet']],
 ['id'=>91020,'name'=>'Amaliy ingliz tili kafedrasi','parent'=>91010,'structureType'=>['code'=>'12','name'=>'Kafedra']],
 ['id'=>91030,'name'=>'Buxgalteriya hisobi va moliya','structureType'=>['code'=>'13','name'=>'Bo‘lim']],
 ['id'=>91040,'name'=>'Yangi aniqlanmagan tuzilma','structureType'=>['code'=>'99']],
],1);
$unit=(new yii\db\Query())->from('org')->where(['hemis_id'=>'91030'])->one();$u=new app\models\User();$u->username='http_office';$u->name='Office profile fixture';$u->role='teacher';$u->org_id=$unit['id'];$u->category='PHD';$u->active=1;$u->must_change_password=0;$u->created_at=time();$u->setPassword('OnlyForIsolated-HttpTests2026!');$u->save(false);
echo "Organization type fixtures ready.\n";
