<?php
require __DIR__.'/http-fixtures.php';
use app\models\User;
use yii\db\Query;
$teacher=User::findOne(['username'=>'http_teacher']);$teacher->category='DARAJASIZ';$teacher->save(false);
foreach(['faculty'=>['faculty',2],'operator'=>['admin',null]] as $name=>[$role,$org]){
    $user=new User();$user->username='http_'.$name;$user->name='HTTP '.$name;$user->role=$role;$user->category='DARAJASIZ';$user->org_id=$org;$user->active=1;$user->must_change_password=0;$user->created_at=time();$user->setPassword('OnlyForIsolated-HttpTests2026!');$user->save(false);
}
Yii::$app->db->createCommand()->delete('route_step')->execute();
foreach(['head','faculty','expert','admin','superadmin'] as $index=>$role)Yii::$app->db->createCommand()->insert('route_step',['position'=>$index+1,'name'=>'Bosqich '.($index+1),'roles'=>json_encode([$role])])->execute();
$indicator=(new Query())->from('indicator')->where(['code'=>'DARAJASIZ-FAN-KATALOGI-FAN'])->one();
if(!$indicator)throw new RuntimeException('Test indicator missing.');
Yii::$app->db->createCommand()->update('period_rule',['enabled'=>1,'calc_type'=>'BirlikUchun','base_score'=>5,'cap_score'=>20,'once_only'=>0,'decision'=>'Isolated application updates test'],['period_id'=>1,'indicator_id'=>$indicator['id']])->execute();
// The read-receipt migration can be reapplied without changing application data.
ob_start();(new m261107_000001_notification_receipts())->up();ob_end_clean();
echo "Application updates fixtures ready.\n";
