<?php
require __DIR__.'/role_scope_fixtures.php';
use app\services\{Access,Workflow,Notifications};
use app\models\User;
use yii\db\Query;
$count=0;
function scopedCheck(bool $ok,string $message):void{global $count;if(!$ok)throw new RuntimeException($message);$count++;echo 'PASS '.$message.PHP_EOL;}
foreach(['teacher'=>[10,1],'head'=>[238,6],'faculty'=>[275,8],'expert'=>[29,2],'operator'=>[365,14],'admin'=>[365,14],'manager'=>[365,14]] as $name=>[$score,$total]){
    $actor=User::findOne(['username'=>'http_'.$name]);$query=Access::scope((new Query())->from(['a'=>'application']),$actor);
    scopedCheck((int)(clone $query)->count()===$total,$name.' row scope');
    scopedCheck((int)$query->andWhere(['a.status'=>'approved'])->sum('a.score_cents')===$score*100,$name.' score scope');
}
$expert=User::findOne(['username'=>'http_expert']);
scopedCheck(!Access::canView($expert,Workflow::application($ids['B-UNREVIEWED'])),'expert does not inherit access to every application of a reviewed employee');
scopedCheck(!Access::canView($expert,Workflow::application($ids['D'])),'expert cannot open an unrelated approved application');
scopedCheck(Access::canView($expert,Workflow::application($ids['EXPERT-QUEUE'])),'expert can open an assigned application before approving it');
scopedCheck(Notifications::unreadCount((int)$expert->id)===1,'expert still receives assigned review notifications');
Workflow::act($expert,$ids['EXPERT-QUEUE'],'approve','Expert scope approval');
$query=Access::scope((new Query())->from(['a'=>'application']),$expert);
scopedCheck((int)(clone $query)->count()===3 && (int)$query->sum('a.score_cents')===3400,'approved assignment joins expert KPI scope once');
scopedCheck(Access::canView($expert,Workflow::application($ids['EXPERT-QUEUE'])),'expert retains access after the review stage completes');
echo "SUCCESS: $count role scope checks completed.\n";
