<?php
// Fresh, isolated fixture database only; never run this against production data.
require __DIR__.'/http-fixtures.php';
use yii\db\Query;
$db=Yii::$app->db;
$teacher=app\models\User::findOne(['username'=>'http_teacher']);
$other=app\models\User::findOne(['username'=>'http_other']);
$admin=app\models\User::findOne(['username'=>'http_admin']);
$db->createCommand()->insert('org',['name'=>'PRIVATE-OTHER-DEPARTMENT','kind'=>'department','parent_id'=>2,'type_verified'=>1])->execute();
$other->org_id=(int)$db->lastInsertID;$other->name='PRIVATE-OTHER-EMPLOYEE';$other->save(false);
$indicator=(new Query())->from('indicator')->where(['category'=>'PHD','deleted_at'=>null])->one();
$ids=[];
foreach([[$teacher,'approved',1500],[$teacher,'submitted',0],[$teacher,'draft',0],[$teacher,'returned',0],[$teacher,'rejected',0],[$other,'approved',9900],[$other,'submitted',0],[$other,'draft',0]] as [$person,$status,$score]){
    $own=$person->id===$teacher->id;$title=($own?'PERSONAL-OWN-':'PRIVATE-OTHER-').$status;
    $db->createCommand()->insert('application',['uuid'=>Yii::$app->security->generateRandomString(32),'employee_id'=>$person->id,'period_id'=>1,'indicator_id'=>$indicator['id'],'category'=>'PHD','title'=>$title,'activity_date'=>'2026-11-07','quantity'=>1,'status'=>$status,'score_cents'=>$score,'created_at'=>time(),'updated_at'=>time()])->execute();
    $ids[$title]=(int)$db->lastInsertID;
}
foreach(['PERSONAL-OWN-approved','PRIVATE-OTHER-approved'] as $title){
    $id=$ids[$title];$score=str_starts_with($title,'PERSONAL')?1500:9900;
    $db->createCommand()->insert('score_entry',['application_id'=>$id,'actor_id'=>$admin->id,'amount_cents'=>$score,'reason'=>'Fixture approval','created_at'=>time()])->execute();
    $db->createCommand()->insert('review',['application_id'=>$id,'actor_id'=>$admin->id,'action'=>'approve','step'=>2,'reason'=>str_starts_with($title,'PERSONAL')?'PERSONAL-OWN-DECISION':'PRIVATE-OTHER-DECISION','created_at'=>time()])->execute();
}
$body="%PDF-1.4\nfixture\n%%EOF";@mkdir(Yii::getAlias('@runtime/evidence'),0700,true);file_put_contents(Yii::getAlias('@runtime/evidence/private.pdf'),$body);
$db->createCommand()->insert('evidence',['application_id'=>$ids['PRIVATE-OTHER-approved'],'name'=>'PRIVATE-OTHER.pdf','storage_name'=>'private.pdf','mime'=>'application/pdf','size'=>strlen($body),'sha256'=>hash('sha256',$body)])->execute();
echo "Teacher workspace fixtures ready.\n";
