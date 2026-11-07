<?php
require __DIR__.'/decision_permissions_fixtures.php';
use app\services\Workflow;
use yii\db\Query;
$count=0;
function checked(bool $ok,string $message):void{global $count;if(!$ok)throw new RuntimeException($message);$count++;echo 'PASS '.$message.PHP_EOL;}
function deniedDecision(callable $call,string $message):void{try{$call();}catch(DomainException $e){checked(true,$message);return;}throw new RuntimeException('Expected denial: '.$message);}
$before=Workflow::application($id);
deniedDecision(fn()=>Workflow::act($operator,$id,'revoke','Forged admin revoke'),'final ordinary administrator cannot revoke');
deniedDecision(fn()=>Workflow::act($head,$id,'revoke','Forged head revoke'),'head cannot revoke');
deniedDecision(fn()=>Workflow::save($operator,$before,[],$id),'approved document cannot be revoked through ordinary-admin edit');
deniedDecision(fn()=>Workflow::removeEvidence($operator,1),'approved evidence cannot be removed by ordinary admin');
checked(Workflow::application($id)['status']==='approved' && (int)Workflow::application($id)['score_cents']===1500,'denied operations preserve the approved score');
checked((int)(new Query())->from('review')->where(['application_id'=>$id,'reason'=>'SUPERADMIN-HIDDEN-REVOKE'])->select('visible_in_history')->scalar()===0,'superadministrator action is excluded from visible document history');
checked((new Query())->from('audit')->where(['action'=>'application_superadmin_revoke'])->exists(),'superadministrator action remains in internal audit');
checked((new Query())->from('review')->where(['application_id'=>$id,'actor_id'=>$operator->id,'visible_in_history'=>1])->exists(),'ordinary reviewer history remains visible');
Workflow::act($super,$id,'revoke','SUPERADMIN-SECOND-REVOKE');
checked(Workflow::application($id)['status']==='review','superadministrator can revoke outside the final route role');
checked((int)(new Query())->from('score_entry')->where(['application_id'=>$id])->sum('amount_cents')===0,'superadministrator revocation reverses the score once');
deniedDecision(fn()=>Workflow::act($super,$id,'revoke','Repeat revoke'),'repeated cancellation is rejected');
Workflow::act($operator,$id,'approve','Admin reapproval');
checked((int)(new Query())->from('score_entry')->where(['application_id'=>$id])->sum('amount_cents')===1500,'reapproval adds the score once');
Workflow::save($super,array_merge(Workflow::application($id),['title'=>'Superadministrator correction']),[],$id);
checked(Workflow::application($id)['status']==='draft','superadministrator retains correction authority');
checked(!(new Query())->from('review')->where(['application_id'=>$id,'actor_id'=>$super->id,'visible_in_history'=>1])->exists(),'superadministrator edits also stay out of visible document history');
// Emulate the previous schema to verify upgrade behavior on existing history rows.
Yii::$app->db->createCommand('ALTER TABLE review DROP COLUMN visible_in_history')->execute();
Yii::$app->db->schema->refreshTableSchema('review');ob_start();(new m261107_000003_review_visibility())->up();ob_end_clean();
checked(!(new Query())->from('review')->where(['actor_id'=>$super->id,'visible_in_history'=>1])->exists(),'migration hides existing superadministrator history without deleting it');
checked((new Query())->from('review')->where(['actor_id'=>$operator->id,'visible_in_history'=>1])->exists(),'migration preserves ordinary reviewer history');
echo "SUCCESS: $count decision permission checks completed.\n";
