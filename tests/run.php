<?php
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
Yii::setAlias('@app',dirname(__DIR__));
$dir=dirname(__DIR__).'/runtime/tests-'.bin2hex(random_bytes(6));mkdir($dir,0700,true);
$config=require dirname(__DIR__).'/config/console.php';$config['runtimePath']=$dir;$config['components']['db']=['class'=>yii\db\Connection::class,'dsn'=>'sqlite:'.$dir.'/test.sqlite'];
new yii\console\Application($config);
require dirname(__DIR__).'/migrations/m261105_000001_initial.php';ob_start();(new m261105_000001_initial())->up();ob_end_clean();require dirname(__DIR__).'/migrations/m261106_000001_employee_profile.php';ob_start();(new m261106_000001_employee_profile())->up();ob_end_clean();require dirname(__DIR__).'/migrations/m261106_000002_superadmin_management.php';ob_start();(new m261106_000002_superadmin_management())->up();ob_end_clean();require dirname(__DIR__).'/migrations/m261106_000003_organization_details.php';ob_start();(new m261106_000003_organization_details())->up();ob_end_clean();Yii::$app->db->schema->refresh();
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
 Hemis::configure('https://student.fdu.uz/',' Bearer TestOnly-Hemis-Key ');
 $stored=Hemis::settings();check(!str_contains(file_get_contents(Yii::getAlias('@runtime/hemis.json')),'TestOnly-Hemis-Key'),'HEMIS token encrypted on disk');
 check(Yii::$app->security->decryptByKey(base64_decode($stored['token']),Yii::$app->params['cookieKey'])==='TestOnly-Hemis-Key','HEMIS token survives reload');
 Hemis::configure('student.fdu.uz','');check(Hemis::settings()===$stored,'blank HEMIS token preserves saved key');
 denied(fn()=>Hemis::configure('other.example.uz',''),'changed HEMIS domain requires new key');
 check(Hemis::settings()===$stored,'failed HEMIS configuration preserves saved key');
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

 $scope=(new Query())->from(['a'=>'application'])->innerJoin(['u'=>'user'],'u.id=a.employee_id')->innerJoin(['i'=>'indicator'],'i.id=a.indicator_id')->innerJoin(['p'=>'period'],'p.id=a.period_id')->where(['a.employee_id'=>$teacher->id,'a.period_id'=>$period]);
 $details=\app\services\Dashboard::details($scope,['starts'=>'2026-01-01','ends'=>'2026-12-31']);
 check(count($details['series'])===12,'dashboard month range uses selected period');
 $sum=(int)(clone $scope)->andWhere(['a.status'=>'approved'])->sum('a.score_cents');
 check(array_sum(array_column($details['series'],'cents'))===$sum,'dashboard chart equals approved activity scores');
 check(count(array_filter($details['awaiting'],fn($a)=>(int)$a['employee_id']!==(int)$teacher->id))===0,'dashboard pending rows preserve employee scope');

 $ordinaryAdmin=user('ordinary_admin','admin',null);
 denied(fn()=>\app\services\AccountPolicy::role($ordinaryAdmin,$teacher,'expert'),'admin cannot grant ordinary role');
 denied(fn()=>\app\services\AccountPolicy::role($ordinaryAdmin,$teacher,'unassigned'),'admin cannot remove role');
 check(\app\services\AccountPolicy::role($ordinaryAdmin,$teacher,null)==='teacher','admin can retain existing role');
 denied(fn()=>\app\services\AccountPolicy::role($ordinaryAdmin,null,null),'only superadmin can add an employee');
 check(\app\services\AccountPolicy::role($admin,$teacher,'expert')==='expert','superadmin can assign role');
 check(\app\services\AccountPolicy::role($admin,$teacher,'unassigned')==='unassigned','superadmin can remove role');
 denied(fn()=>\app\services\Organization::save('Bad faculty','faculty',$department),'faculty cannot attach to department');
 denied(fn()=>\app\services\Organization::save('Bad department','department',$university),'department cannot attach directly to university');
 $root=\app\services\Organization::save('Manual University','university',null);
 $fac=\app\services\Organization::save('Manual Faculty','faculty',$root);
 $dep=\app\services\Organization::save('Manual Department','department',$fac);
 $labels=\app\services\Organization::labels((new Query())->from('org')->all());check($labels[$dep]==='Manual University → Manual Faculty → Manual Department','complete assignment path');
 denied(fn()=>Hemis::applyStructure($structure),'multiple universities require explicit selection');
 check(Hemis::applyStructure($structure,$root)['saved']===2,'HEMIS imports into selected manual university');
 check($imported->role==='unassigned','HEMIS cannot grant a role');
 $imported->role='head';$imported->active=1;$imported->category='PHD';$imported->save(false);$passwordHash=$imported->password_hash;
 Hemis::applyEmployees([['id'=>501,'full_name'=>'Complete Employee','_department'=>['id'=>101],'academicDegree'=>['name'=>'PhD'],'academicRank'=>['name'=>'Dotsent'],'staffPosition'=>['name'=>'Professor'],'employmentForm'=>['name'=>'Asosiy'],'employmentStaff'=>['name'=>'1.0'],'phone'=>'+998000000000','email'=>'test@example.test','image'=>'/static/test.jpg']]);
 $updated=User::findOne($imported->id);$profile=json_decode($updated->hemis_profile,true);
 check($updated->role==='head' && $updated->active && $updated->category==='PHD' && $updated->password_hash===$passwordHash,'HEMIS preserves roles, activity, category and password');
 check($updated->org_id!==null && $updated->degree==='PhD' && $updated->position==='Professor' && $profile['academic_rank']==='Dotsent' && $profile['employment_staff']==='1.0' && $profile['email']==='test@example.test','complete HEMIS profile and department linked');
 check(\app\services\EmployeePhoto::url('/static/test.jpg','student.fdu.uz')==='https://student.fdu.uz/static/test.jpg','HEMIS relative employee image');
 check(\app\services\EmployeePhoto::url('javascript:alert(1)','student.fdu.uz')===null,'unsafe employee image rejected');
 check(\app\services\EmployeePhoto::url('https://cdn.example.test/photo','student.fdu.uz')==='https://cdn.example.test/photo','HTTPS HEMIS photo CDN supported without forwarding API key');
 Hemis::applyEmployees([['id'=>501,'full_name'=>'Complete Employee']]);$updated=User::findOne($imported->id);$profile=json_decode($updated->hemis_profile,true);
 check($updated->org_id===null && $updated->degree===null && $profile['phone']===null,'missing HEMIS values stored as absent rather than stale');
 $approved=Workflow::application($id);denied(fn()=>Workflow::save($teacher,$approved,[],$id),'teacher cannot edit approved document');
 Workflow::save($ordinaryAdmin,array_merge($approved,['title'=>'Administrator correction']),[],$id);
 $corrected=Workflow::application($id);check($corrected['status']==='draft' && (int)$corrected['employee_id']===(int)$teacher->id && $corrected['category']===$approved['category'],'admin edits another employee document without changing ownership/category');
 check((int)(new Query())->from('score_entry')->where(['application_id'=>$id])->sum('amount_cents')===0 && (int)$corrected['score_cents']===0,'admin correction reverses previous score exactly once');
 Workflow::save($admin,$corrected,[],$id);check((int)(new Query())->from('score_entry')->where(['application_id'=>$id])->sum('amount_cents')===0,'repeated admin correction does not reverse score twice');
 check((new Query())->from('audit')->where(['action'=>'application_admin_edit'])->exists(),'admin correction retains before and after audit');
 Yii::$app->db->createCommand()->update('period',['closed'=>1],['id'=>$period])->execute();Workflow::save($ordinaryAdmin,$corrected,[],$id);check(Workflow::application($id)['status']==='draft','admin may correct closed-period document');

 $manualFaculty=\app\services\Organization::save('Matched Faculty','faculty',$root);
 $manualDepartment=\app\services\Organization::save('Matched Department','department',$manualFaculty);
 Hemis::applyStructure([['id'=>901,'name'=>'Matched Faculty','structureType'=>['code'=>'12']],['id'=>902,'name'=>'Matched Department','structureType'=>['code'=>'13'],'parent'=>['id'=>901]]],$root);
 check((int)(new Query())->from('org')->where(['hemis_id'=>'901'])->scalar()===$manualFaculty,'HEMIS reuses matching manual faculty');
 check((int)(new Query())->from('org')->where(['hemis_id'=>'902'])->scalar()===$manualDepartment,'HEMIS reuses matching manual department');

 try{\app\services\Management::deleteOrganization($ordinaryAdmin,$root);throw new RuntimeException('Expected superadmin restriction');}catch(\yii\web\ForbiddenHttpException $e){check(true,'admin cannot delete organization');}
 denied(fn()=>\app\services\Management::deleteOrganization($admin,$root),'cannot delete university containing faculties');
 $emptyOrg=\app\services\Organization::save('Delete fixture','university',null);\app\services\Management::deleteOrganization($admin,$emptyOrg);check(!(new Query())->from('org')->where(['id'=>$emptyOrg])->exists(),'superadmin deletes empty organization');
 $deleted=user('deleted_employee','teacher',$dep);$deleted->hemis_id='777';$deleted->save(false);\app\services\Management::deleteUser($admin,(int)$deleted->id);check(User::findIdentity($deleted->id)===null,'deleted employee cannot authenticate');
 $report=Hemis::applyEmployees([['id'=>777,'full_name'=>'Do not restore']]);check($report['saved']===0 && $report['skipped']===1,'HEMIS does not recreate deleted employee');
 $criterion=['code'=>'CUSTOM-DELETE-TEST','name'=>'Custom test criterion','section'=>'CUSTOM','section_name'=>'Custom direction','criterion_code'=>'CUSTOM-1','criterion_name'=>'Custom condition','category'=>'PHD','calc_type'=>'BirlikUchun','base_score'=>'2','policy_required'=>1];
 $custom=\app\services\Management::saveIndicator($admin,$criterion);check((new Query())->from('indicator')->where(['id'=>$custom])->exists(),'superadmin creates criterion');
 \app\services\Management::saveIndicator($admin,array_merge($criterion,['name'=>'Edited test criterion']),$custom);check((new Query())->from('indicator')->where(['id'=>$custom])->one()['name']==='Edited test criterion','superadmin edits criterion');
 \app\services\Management::deleteIndicator($admin,$custom);check((new Query())->from('indicator')->where(['id'=>$custom])->one()['deleted_at']!==null,'superadmin deletes criterion without breaking history');
 $catalogCount=(int)(new Query())->from('indicator')->count();Catalog::import();check((int)(new Query())->from('indicator')->count()===$catalogCount,'startup does not resurrect deleted criteria');
 $badReport=Hemis::applyEmployees([null,['id'=>[],'name'=>[]],['id'=>999,'first_name'=>'Only','last_name'=>'Names']]);check($badReport['saved']===1 && $badReport['skipped']===2 && !empty($badReport['reasons']),'malformed employee rows reported without aborting whole import');

 $historyCount=(int)(new Query())->from('application')->where(['employee_id'=>$teacher->id])->count();
 \app\services\Management::deleteUser($admin,(int)$teacher->id);check((int)(new Query())->from('application')->where(['employee_id'=>$teacher->id])->count()===$historyCount && User::findOne($teacher->id)!==null,'employee deletion preserves linked documents');
 \app\services\Management::deleteIndicator($admin,(int)$patent['id']);Catalog::import();check((new Query())->from('indicator')->where(['id'=>$patent['id']])->one()['deleted_at']!==null && Workflow::application($id)['indicator_id']==$patent['id'],'source criterion deletion survives reimport and preserves linked documents');

 $blockRoot=\app\services\Organization::save('Block University','university',null);$blockFaculty=\app\services\Organization::save('Block Faculty','faculty',$blockRoot);$blockDepartment=\app\services\Organization::save('Block Department','department',$blockFaculty);
 $blockTeacher=user('blocked_teacher','teacher',$blockDepartment);
 $blockPeriod=insert('period',['name'=>'Block test period','starts'=>'2026-01-01','ends'=>'2026-12-31','closed'=>0]);Catalog::createRules($blockPeriod);
 $blockDraft=fixture($blockTeacher,$blockPeriod,(int)$cap['id'],'Before block');
 \app\services\Organization::setBlocked($admin,$blockFaculty,true);
 check(\app\services\Organization::blockedBy($blockDepartment)['id']==$blockFaculty,'faculty block inherited by department');
 Yii::$app->db->createCommand()->update('org',['hemis_id'=>'6001'],['id'=>$blockFaculty])->execute();Yii::$app->db->createCommand()->update('org',['hemis_id'=>'6002'],['id'=>$blockDepartment])->execute();
 $blockedImport=Hemis::applyEmployees([['id'=>6003,'full_name'=>'Blocked assignment fixture','department'=>['id'=>6002]]]);check(User::findOne(['hemis_id'=>'6003'])->org_id===null && !empty($blockedImport['reasons']['Bo‘lim bloklangan']),'HEMIS cannot bypass blocked employee assignment');
 $blockedStructure=Hemis::applyStructure([['id'=>6004,'name'=>'Blocked new department','structureType'=>['code'=>'13'],'parent'=>6001]],$blockRoot);check($blockedStructure['saved']===0 && $blockedStructure['skipped']===1,'HEMIS cannot create child under blocked faculty');
 try{\app\services\Organization::saveDetails($ordinaryAdmin,['name'=>'Move around block','parent_id'=>$fac],$blockDepartment,'department');throw new RuntimeException('Expected block protection');}catch(\yii\web\ForbiddenHttpException $e){check(true,'admin cannot escape inherited block by reparenting');}

 denied(fn()=>Workflow::save($blockTeacher,['period_id'=>$blockPeriod,'indicator_id'=>$cap['id'],'title'=>'Blocked new','activity_date'=>'2026-06-01','quantity'=>1],[]),'blocked department prevents new activities');
 denied(fn()=>Workflow::act($blockTeacher,$blockDraft,'submit'),'blocked department prevents new submission');
 denied(fn()=>\app\services\Organization::saveDetails($admin,['name'=>'Blocked child','parent_id'=>$blockFaculty],null,'department'),'cannot create new child under blocked faculty');
 try{\app\services\Organization::setBlocked($ordinaryAdmin,$blockFaculty,false);throw new RuntimeException('Expected denial');}catch(\yii\web\ForbiddenHttpException $e){check(true,'admin cannot bypass superadmin block');}
 \app\services\Organization::saveDetails($ordinaryAdmin,['name'=>'Edited blocked department','parent_id'=>$blockFaculty,'phone'=>'+998000','email'=>'department@example.test'],$blockDepartment,'department');
 check((new Query())->from('org')->where(['id'=>$blockDepartment])->one()['email']==='department@example.test','all organization requisites saved on dedicated form');
 \app\services\Organization::setBlocked($admin,$blockFaculty,false);check(\app\services\Organization::blockedBy($blockDepartment)===null,'unblock restores department operations');
 Workflow::act($blockTeacher,$blockDraft,'submit');check(Workflow::application($blockDraft)['status']==='submitted','submission works after unblock');
 echo "\nSUCCESS: $count checks completed.\n";
}catch(Throwable $e){fwrite(STDERR,$e."\n");exit(1);}
