<?php
require __DIR__.'/application_updates_fixtures.php';
use app\models\User;
$db=Yii::$app->db;
$db->createCommand()->insert('org',['name'=>'Foreign faculty','kind'=>'faculty','parent_id'=>1,'type_verified'=>1])->execute();$faculty=(int)$db->lastInsertID;
$db->createCommand()->insert('org',['name'=>'Foreign department','kind'=>'department','parent_id'=>$faculty,'type_verified'=>1])->execute();$department=(int)$db->lastInsertID;
foreach(['head_peer'=>['head',3],'head_foreign'=>['head',$department],'faculty_foreign'=>['faculty',$faculty]] as $name=>[$role,$org]){
    $user=new User();$user->username='http_'.$name;$user->name='HTTP '.$name;$user->role=$role;$user->category='DARAJASIZ';$user->org_id=$org;$user->active=1;$user->must_change_password=0;$user->created_at=time();$user->setPassword('OnlyForIsolated-HttpTests2026!');$user->save(false);
}
echo "Reviewer notification fixtures ready.\n";
