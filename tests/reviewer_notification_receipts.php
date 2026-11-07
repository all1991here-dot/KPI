<?php
// This fixture runner refuses any runtime not explicitly marked as an isolated HTTP test.
require __DIR__.'/reviewer_notifications_fixtures.php';
use app\services\{Notifications,Workflow};
use app\models\User;
use yii\db\Query;
$count=0;
function verify(bool $ok,string $name):void{global $count;if(!$ok)throw new RuntimeException($name);$count++;echo 'PASS '.$name.PHP_EOL;}
$teacher=User::findOne(['username'=>'http_teacher']);$head=User::findOne(['username'=>'http_head']);$peer=User::findOne(['username'=>'http_head_peer']);$foreign=User::findOne(['username'=>'http_head_foreign']);
function submitted(User $owner):int{
    $indicator=(new Query())->from('indicator')->where(['category'=>$owner->category,'deleted_at'=>null])->one();
    Yii::$app->db->createCommand()->update('period_rule',['enabled'=>1,'calc_type'=>'BirlikUchun','base_score'=>5,'once_only'=>0],['period_id'=>1,'indicator_id'=>$indicator['id']])->execute();
    $id=Workflow::save($owner,['period_id'=>1,'indicator_id'=>$indicator['id'],'title'=>'Receipt test '.$owner->id,'activity_date'=>'2026-11-07','quantity'=>1],[]);
    Yii::$app->db->createCommand()->insert('evidence',['application_id'=>$id,'name'=>'fixture.pdf','storage_name'=>'fixture-'.$id.'.pdf','mime'=>'application/pdf','size'=>10,'sha256'=>hash('sha256','fixture-'.$id)])->execute();
    Workflow::act($owner,$id,'submit');return (int)(new Query())->from('review')->where(['application_id'=>$id])->max('id');
}
$assigned=submitted($teacher);$own=submitted($head);
Notifications::markRead((int)$head->id,$own);
verify($own>$assigned,'owner cursor can be newer than an already pending review task');
verify(Notifications::unreadCount((int)$head->id)===1,'existing review assignment remains unread independently of owner messages');
verify(!in_array($own,Notifications::assignmentIds((int)$head->id),true),'reviewer cannot receive their own application as a review task');
verify(Notifications::unreadCount((int)$peer->id)===2,'another eligible head receives both applications');
verify(Notifications::unreadCount((int)$foreign->id)===0,'foreign department receives no notification');
Notifications::markRead((int)$head->id,$assigned,true);
verify(Notifications::unreadCount((int)$head->id)===0,'opening review messages marks that recipient read');
Notifications::markRead((int)$head->id,$assigned-1,true);
verify(Notifications::lastRead((int)$head->id,true)===$assigned,'older tab cannot move review cursor backwards');
verify(Notifications::unreadCount((int)$peer->id)===2,'one reviewer reading does not mark a peer reviewer read');
Yii::$app->db->createCommand()->update('period',['closed'=>1],['id'=>1])->execute();
verify(Notifications::assignmentIds((int)$peer->id)===[],'closed periods produce no actionable review notification');
Yii::$app->db->createCommand()->update('period',['closed'=>0],['id'=>1])->execute();
$peer->active=0;$peer->save(false);
verify(Notifications::assignmentIds((int)$peer->id)===[],'inactive reviewer receives no assignment');
echo "SUCCESS: $count reviewer notification checks completed.\n";
