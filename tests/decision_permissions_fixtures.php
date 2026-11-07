<?php
require __DIR__.'/application_updates_fixtures.php';
use app\services\Workflow;
use app\models\User;
use yii\db\Query;
Yii::$app->db->createCommand()->delete('route_step')->execute();
foreach(['head','admin'] as $index=>$role)Yii::$app->db->createCommand()->insert('route_step',['position'=>$index+1,'name'=>'Decision stage '.($index+1),'roles'=>json_encode([$role])])->execute();
$teacher=User::findOne(['username'=>'http_teacher']);$head=User::findOne(['username'=>'http_head']);$operator=User::findOne(['username'=>'http_operator']);$super=User::findOne(['username'=>'http_admin']);
$indicator=(new Query())->from('indicator')->where(['code'=>'DARAJASIZ-FAN-KATALOGI-FAN'])->one();
$id=Workflow::save($teacher,['period_id'=>1,'indicator_id'=>$indicator['id'],'title'=>'DECISION-PERMISSION-TEST','activity_date'=>'2026-11-07','quantity'=>3],[]);
$body="%PDF-1.4\nDecision test\n%%EOF";@mkdir(Yii::getAlias('@runtime/evidence'),0700,true);file_put_contents(Yii::getAlias('@runtime/evidence/decision.pdf'),$body);
Yii::$app->db->createCommand()->insert('evidence',['application_id'=>$id,'name'=>'proof.pdf','storage_name'=>'decision.pdf','mime'=>'application/pdf','size'=>strlen($body),'sha256'=>hash('sha256',$body)])->execute();
Workflow::act($teacher,$id,'submit');Workflow::act($head,$id,'approve','HEAD-VISIBLE-APPROVAL');Workflow::act($operator,$id,'approve','ADMIN-VISIBLE-APPROVAL');
Workflow::act($super,$id,'revoke','SUPERADMIN-HIDDEN-REVOKE');Workflow::act($operator,$id,'approve','ADMIN-VISIBLE-REAPPROVAL');
echo "Decision permission fixtures ready.\n";
