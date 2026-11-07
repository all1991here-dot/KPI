<?php
require __DIR__.'/reviewer_notifications_fixtures.php';
use app\models\User;
use yii\db\Query;
$db=Yii::$app->db;
// Keep the operational fixture's resubmission route consistent with its pending expert step.
$db->createCommand()->delete('route_step')->execute();
$db->createCommand()->insert('route_step',['position'=>1,'name'=>'Expert stage','roles'=>'["expert"]'])->execute();
$db->createCommand()->insert('org',['name'=>'Same faculty other department','kind'=>'department','parent_id'=>2,'type_verified'=>1])->execute();$sameFaculty=(int)$db->lastInsertID;
$db->createCommand()->insert('org',['name'=>'Nested department unit','kind'=>'unit','parent_id'=>3,'type_verified'=>1])->execute();$nested=(int)$db->lastInsertID;
$foreign=User::findOne(['username'=>'http_head_foreign'])->org_id;
foreach(['scope_c'=>$sameFaculty,'scope_d'=>$foreign,'scope_nested'=>$nested] as $name=>$org){
    $user=new User();$user->username='http_'.$name;$user->name='HTTP '.$name;$user->role='teacher';$user->category='DARAJASIZ';$user->org_id=$org;$user->active=1;$user->must_change_password=0;$user->created_at=time();$user->setPassword('OnlyForIsolated-HttpTests2026!');$user->save(false);
}
$indicator=(new Query())->from('indicator')->where(['code'=>'DARAJASIZ-FAN-KATALOGI-FAN'])->one();
$db->createCommand()->update('period_rule',['cap_score'=>null],['period_id'=>1,'indicator_id'=>$indicator['id']])->execute();
$rule=(new Query())->from('period_rule')->where(['period_id'=>1,'indicator_id'=>$indicator['id']])->one();
$ids=[];
foreach([['teacher','A',10],['other','B-APPROVED',20],['other','B-UNREVIEWED',200],['scope_c','C',30],['scope_d','D',40],['scope_nested','NESTED',3],['head','HEAD',5],['faculty','FACULTY',7],['expert','EXPERT',9],['operator','ADMIN',11],['admin','SUPER',13],['manager','MANAGEMENT',17]] as [$name,$key,$score]){
    $user=User::findOne(['username'=>'http_'.$name]);$user->category='DARAJASIZ';if(in_array($name,['expert','admin','manager'],true))$user->org_id=null;$user->save(false);
    $db->createCommand()->insert('application',['uuid'=>Yii::$app->security->generateRandomString(32),'employee_id'=>$user->id,'period_id'=>1,'indicator_id'=>$indicator['id'],'category'=>'DARAJASIZ','title'=>'SCOPE-'.$key,'activity_date'=>'2026-11-07','quantity'=>1,'status'=>'approved','score_cents'=>$score*100,'created_at'=>time(),'updated_at'=>time()])->execute();$ids[$key]=(int)$db->lastInsertID;
}
$expert=User::findOne(['username'=>'http_expert']);$other=User::findOne(['username'=>'http_other']);$foreignUser=User::findOne(['username'=>'http_scope_d']);
$db->createCommand()->insert('review',['application_id'=>$ids['B-APPROVED'],'actor_id'=>$expert->id,'action'=>'approve','step'=>2,'reason'=>'Scope fixture approval','created_at'=>time()])->execute();
foreach([['B-DRAFT',$other,'draft',null],['EXPERT-QUEUE',$foreignUser,'submitted',json_encode([['name'=>'Expert stage','roles'=>'["expert"]']])]] as [$key,$user,$status,$route]){
    $db->createCommand()->insert('application',['uuid'=>Yii::$app->security->generateRandomString(32),'employee_id'=>$user->id,'period_id'=>1,'indicator_id'=>$indicator['id'],'category'=>'DARAJASIZ','title'=>'SCOPE-'.$key,'activity_date'=>'2026-11-07','quantity'=>1,'status'=>$status,'score_cents'=>0,'rule_snapshot'=>json_encode($rule),'route_snapshot'=>$route,'created_at'=>time(),'updated_at'=>time()])->execute();$ids[$key]=(int)$db->lastInsertID;
}
$db->createCommand()->insert('review',['application_id'=>$ids['EXPERT-QUEUE'],'actor_id'=>$foreignUser->id,'action'=>'submit','step'=>0,'reason'=>'Expert queue fixture','created_at'=>time()])->execute();
$body="%PDF-1.4\nScope test\n%%EOF";@mkdir(Yii::getAlias('@runtime/evidence'),0700,true);file_put_contents(Yii::getAlias('@runtime/evidence/scope.pdf'),$body);
$db->createCommand()->insert('evidence',['application_id'=>$ids['EXPERT-QUEUE'],'name'=>'scope.pdf','storage_name'=>'scope.pdf','mime'=>'application/pdf','size'=>strlen($body),'sha256'=>hash('sha256',$body)])->execute();
echo "Role scope fixtures ready.\n";
