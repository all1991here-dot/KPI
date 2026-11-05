<?php
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@app',dirname(__DIR__));
$dir=dirname(__DIR__).'/runtime/tests-'.bin2hex(random_bytes(6));mkdir($dir,0700,true);
$config=require dirname(__DIR__).'/config/console.php';$config['runtimePath']=$dir;$config['components']['db']=['class'=>yii\db\Connection::class,'dsn'=>'sqlite:'.$dir.'/test.sqlite'];
new yii\console\Application($config);
require dirname(__DIR__).'/migrations/m261105_000001_initial.php';ob_start();(new m261105_000001_initial())->up();ob_end_clean();Yii::$app->db->schema->refresh();
use app\services\{Catalog,Workflow,ScoreCalculator,Access,Hemis};use app\models\User;use yii\db\Query;
$count=0;
function check(bool $condition,string $name):void{global $count;if(!$condition)throw new RuntimeException('FAIL '.$name);$count++;echo "PASS $name\n";}
function denied(callable $call,string $name):void{try{$call();}catch(DomainException $e){check(true,$name);return;}throw new RuntimeException('FAIL expected rejection: '.$name);}
function insert(string $table,array $values):int{Yii::$app->db->createCommand()->insert($table,$values)->execute();return (int)Yii::$app->db->lastInsertID;}
function user(string $name,string $role,?int $org,string $category='PHD'):User{$u=new User();$u->username=$name;$u->name=$name;$u->role=$role;$u->category=$category;$u->org_id=$org;$u->active=1;$u->must_change_password=0;$u->created_at=time();$u->setPassword('TestOnly-Password-2026!');$u->save(false);return $u;}
function fixture(User $u,int $period,int $indicator,string $name='Faoliyat',int $quantity=1):int{
 $id=Workflow::save($u,['period_id'=>$period,'indicator_id'=>$indicator,'title'=>$name,'activity_date'=>'2026-06-01','quantity'=>$quantity],[]);
 insert('evidence',['application_id'=>$id,'name'=>'fixture.pdf','storage_name'=>'fixture.pdf','mime'=>'application/pdf','size'=>100,'sha256'=>hash('sha256',(string)$id)]);return $id;
}
function rule(string $code):array{return(new Query())->from('indicator')->where(['code'=>$code])->one();}
function approve(User $head,User $expert,User $admin,int $id):void{Workflow::act($head,$id,'approve','Kafedra tasdiqladi');Workflow::act($expert,$id,'approve','Ekspert tasdiqladi');Workflow::act($admin,$id,'approve','Yakuniy tasdiq');}
try{
 check(Catalog::import()===139,'139 indicators imported');check(Catalog::import()===0,'catalog import idempotent');
 check((int)(new Query())->from('indicator')->where(['policy_required'=>1])->count()===96,'96 policy decisions preserved');
 $university=insert('org',['name'=>'U','kind'=>'university']);$faculty=insert('org',['name'=>'F','kind'=>'faculty','parent_id'=>$university]);$department=insert('org',['name'=>'D','kind'=>'department','parent_id'=>$faculty]);$otherOrg=insert('org',['name'=>'Other D','kind'=>'department','parent_id'=>$faculty]);
 $teacher=user('teacher','teacher',$department);$other=user('other','teacher',$otherOrg);$head=user('head','head',$department);$expert=user('expert','expert',null);$admin=user('admin','superadmin',null);
 $period=insert('period',['name'=>'2026','starts'=>'2026-01-01','ends'=>'2026-12-31','closed'=>0]);Catalog::createRules($period);Catalog::createRules($period);check((int)(new Query())->from('period_rule')->where(['period_id'=>$period])->count()===139,'period rules idempotent');
 foreach([[1,['head']],[2,['expert','faculty']],[3,['superadmin','admin']]] as [$i,$roles])insert('route_step',['position'=>$i,'name'=>'Step '.$i,'roles'=>json_encode($roles)]);
 $patent=rule('PHD-LOYIHA-PATENT');$id=fixture($teacher,$period,(int)$patent['id']);check(Access::canView($teacher,Workflow::application($id)),'owner sees own draft');check(!Access::canView($other,Workflow::application($id)),'other teacher cannot see draft');
 Workflow::act($teacher,$id,'submit');check(Workflow::application($id)['status']==='submitted','submission queued');
 denied(fn()=>Workflow::act($admin,$id,'approve','Skip'),'cannot skip route');denied(fn()=>Workflow::act($teacher,$id,'approve','Self'),'self approval forbidden');
 approve($head,$expert,$admin,$id);check(Workflow::application($id)['status']==='approved','three-stage approval');check((int)Workflow::application($id)['score_cents']===500,'patent 5 points');
 denied(fn()=>Workflow::act($admin,$id,'approve','Again'),'approval cannot repeat');
 Workflow::act($admin,$id,'revoke','Correction');check((int)(new Query())->from('score_entry')->where(['application_id'=>$id])->sum('amount_cents')===0,'revoke reverses ledger');
 Workflow::act($admin,$id,'approve','Approve corrected');check((int)(new Query())->from('score_entry')->where(['application_id'=>$id])->sum('amount_cents')===500,'reapproval ledger correct');
 $cap=rule('PHD-TASHKILIY-HAKAM');$a=fixture($teacher,$period,(int)$cap['id'],'Hakamlik',2);Workflow::act($teacher,$a,'submit');approve($head,$expert,$admin,$a);
 $b=fixture($teacher,$period,(int)$cap['id'],'Hakamlik 2',2);Workflow::act($teacher,$b,'submit');approve($head,$expert,$admin,$b);check((int)Workflow::application($b)['score_cents']===100,'period cap counts prior approvals');
 $zero=fixture($teacher,$period,(int)$cap['id'],'Limitdan keyin',1);Workflow::act($teacher,$zero,'submit');approve($head,$expert,$admin,$zero);check((int)Workflow::application($zero)['score_cents']===0,'cap can award zero');Workflow::act($admin,$zero,'revoke','Zero correction');check(Workflow::application($zero)['status']==='review','zero-point approval revocable');
 $foreign=fixture($other,$period,(int)$patent['id']);Workflow::act($other,$foreign,'submit');denied(fn()=>Workflow::act($head,$foreign,'approve','Outside'),'department scope enforced');
 denied(fn()=>Workflow::act($expert,$foreign,'return','Wrong stage'),'expert cannot act at head step');
}catch(Throwable $e){fwrite(STDERR,$e."\n");exit(1);}

try{
 Workflow::act($head,$b,'revoke','Outside final step');throw new RuntimeException('Non-final role revoked');
}catch(DomainException $e){check(true,'only final role revokes');}
try{
 $returnId=fixture($teacher,$period,(int)$patent['id'],'Return');Workflow::act($teacher,$returnId,'submit');Workflow::act($head,$returnId,'return','Dalil aniqlashtirilsin');check(Workflow::application($returnId)['status']==='returned','return for revision');
 denied(fn()=>Workflow::act($teacher,$returnId,'appeal',''),'appeal reason mandatory');Workflow::act($teacher,$returnId,'appeal','Hujjat asosida qayta ko‘ring');Workflow::act($head,$returnId,'reject','Asos yetarli emas');check(Workflow::application($returnId)['status']==='rejected','rejection persisted');
 $blocked=rule('PHD-NASHR-Q1Q2');$blockedId=fixture($teacher,$period,(int)$blocked['id']);denied(fn()=>Workflow::act($teacher,$blockedId,'submit'),'unresolved policy blocked');
 $noEvidence=Workflow::save($teacher,['period_id'=>$period,'indicator_id'=>$patent['id'],'title'=>'No evidence','activity_date'=>'2026-01-01','quantity'=>1],[]);denied(fn()=>Workflow::act($teacher,$noEvidence,'submit'),'evidence mandatory');
 $dupe=fixture($teacher,$period,(int)$patent['id']);$existing=(new Query())->from('evidence')->where(['application_id'=>$id])->one();Yii::$app->db->createCommand()->update('evidence',['sha256'=>$existing['sha256']],['application_id'=>$dupe])->execute();denied(fn()=>Workflow::act($teacher,$dupe,'submit'),'duplicate file hash blocked');
 denied(fn()=>Workflow::save($teacher,['period_id'=>$period,'indicator_id'=>$patent['id'],'title'=>'Bad date','activity_date'=>'2025-01-01','quantity'=>1],[]),'out of period date rejected');
 denied(fn()=>Workflow::save($teacher,['period_id'=>$period,'indicator_id'=>rule('DSC-LOYIHA-PATENT')['id'],'title'=>'Wrong category','activity_date'=>'2026-01-01','quantity'=>1],[]),'wrong category rejected');
 Yii::$app->db->createCommand()->update('period',['closed'=>1],['id'=>$period])->execute();denied(fn()=>Workflow::act($admin,$b,'revoke','Closed'),'closed period cannot change');Yii::$app->db->createCommand()->update('period',['closed'=>0],['id'=>$period])->execute();
 $r=['enabled'=>1,'once_only'=>0,'calc_type'=>'BirlikUchun','base_score'=>'0.50','upper_score'=>null,'cap_score'=>'3.00'];check(ScoreCalculator::calculate($r,8,null)===300,'fractional capped arithmetic');
 $r['calc_type']='Ballgacha';$r['upper_score']=5;check(ScoreCalculator::calculate($r,1,4)===400,'expert score accepted');denied(fn()=>ScoreCalculator::calculate($r,1,6),'expert upper bound');
 $r['calc_type']='PulFormulasi';$r['base_score']=5;$r['cap_score']=null;$r['formula_divisor']=100000000;$r['formula_mode']='proportional';check(ScoreCalculator::calculate($r,1,null,0,0,150000000)===750,'proportional grant formula');$r['formula_mode']='floor';check(ScoreCalculator::calculate($r,1,null,0,0,150000000)===500,'whole-block grant formula');
 $r['calc_type']='XirshIndeksi';$r['base_score']=1;check(ScoreCalculator::calculate($r,1,null,0,0,7)===700,'H index configured rule');denied(fn()=>ScoreCalculator::calculate($r,1,null,0,0,1.5),'H index integer only');
 $structure=[['id'=>100,'name'=>'Imported Faculty','structureType'=>['code'=>'12']],['id'=>101,'name'=>'Imported Department','parent'=>100,'structureType'=>['code'=>'13']]];
 check(Hemis::applyStructure($structure)['saved']===2,'HEMIS structure fixture');check(Hemis::applyStructure($structure)['saved']===2,'HEMIS structure idempotent');check((int)(new Query())->from('org')->where(['hemis_id'=>'101'])->count()===1,'no duplicate imported org');
 $employees=[['id'=>501,'full_name'=>'Import Test','department'=>['id'=>101]]];check(Hemis::applyEmployees($employees)['saved']===1,'HEMIS employee fixture');Hemis::applyEmployees($employees);$imported=User::findOne(['hemis_id'=>'501']);check(!$imported->active,'imported accounts inactive by default');check((int)User::find()->where(['hemis_id'=>'501'])->count()===1,'no duplicate imported employee');
 $snapshot=Workflow::application($id)['rule_snapshot'];Yii::$app->db->createCommand()->update('period_rule',['base_score'=>999,'revision'=>2],['period_id'=>$period,'indicator_id'=>$patent['id']])->execute();check(Workflow::application($id)['rule_snapshot']===$snapshot && (int)Workflow::application($id)['score_cents']===500,'approved snapshot remains immutable');

 $exclusiveRule=(new Query())->from('period_rule')->where(['period_id'=>$period,'indicator_id'=>$cap['id']])->one();
 Yii::$app->db->createCommand()->update('period_rule',['exclusive_group'=>'test_exclusive'],['id'=>$exclusiveRule['id']])->execute();
 $first=fixture($teacher,$period,(int)$cap['id'],'Exclusive first');Workflow::act($teacher,$first,'submit');approve($head,$expert,$admin,$first);
 $second=fixture($teacher,$period,(int)$cap['id'],'Exclusive second');Workflow::act($teacher,$second,'submit');Workflow::act($head,$second,'approve','Head');Workflow::act($expert,$second,'approve','Expert');denied(fn()=>Workflow::act($admin,$second,'approve','Final'),'exclusive group enforced even for zero-score result');
 $raw=['enabled'=>1,'once_only'=>0,'calc_type'=>'XirshIndeksi','base_score'=>1000000,'upper_score'=>null,'cap_score'=>null];denied(fn()=>ScoreCalculator::calculate($raw,1,null,0,0,1000000000000),'score overflow rejected');
 check((new Query())->from('review')->where(['application_id'=>$id,'action'=>'revoke'])->exists(),'reversal retained in audit history');
 echo "\nSUCCESS: $count checks completed.\n";
}catch(Throwable $e){fwrite(STDERR,$e."\n");exit(1);}
