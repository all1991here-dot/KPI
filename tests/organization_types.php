<?php
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';Yii::setAlias('@app',dirname(__DIR__));
$dir=dirname(__DIR__).'/runtime/org-types-'.bin2hex(random_bytes(5));mkdir($dir,0700,true);$config=require dirname(__DIR__).'/config/console.php';$config['runtimePath']=$dir;$config['components']['db']=['class'=>yii\db\Connection::class,'dsn'=>'sqlite:'.$dir.'/test.sqlite'];new yii\console\Application($config);
foreach(glob(dirname(__DIR__).'/migrations/*.php') as $file){require $file;$class=pathinfo($file,PATHINFO_FILENAME);ob_start();(new $class())->up();ob_end_clean();}Yii::$app->db->schema->refresh();
use app\services\{Organization,OrganizationClassifier,Hemis,Access};use app\models\User;use yii\db\Query;
$count=0;function check($ok,$name){global $count;if(!$ok)throw new RuntimeException($name);echo "PASS $name\n";$count++;}
function person($login,$role,$org){$u=new User();$u->username=$login;$u->name=$login;$u->role=$role;$u->active=1;$u->must_change_password=0;$u->org_id=$org;$u->created_at=time();$u->setPassword('Isolated-Organization-Test2026!');$u->save(false);return $u;}
$root=Organization::save('Farg‘ona davlat universiteti','university',null);
$wrong=Organization::save('Amaliy ingliz tili kafedrasi','faculty',$root);Yii::$app->db->createCommand()->update('org',['hemis_id'=>'150'],['id'=>$wrong])->execute();
$plain=Organization::save('Adabiyotshunoslik','faculty',$root);Yii::$app->db->createCommand()->update('org',['hemis_id'=>'151'],['id'=>$plain])->execute();
$teacher=person('type_employee','teacher',$wrong);$badFacultyRole=person('wrong_faculty_role','faculty',$wrong);$super=person('type_super','superadmin',null);
OrganizationClassifier::repairLegacy();
check((new Query())->from('org')->where(['id'=>$wrong])->one()['kind']==='department','explicit kafedra name no longer classified as faculty');
check((new Query())->from('org')->where(['id'=>$plain])->one()['type_verified']==0,'plain legacy names are flagged rather than guessed');
$items=[
 ['id'=>1,'name'=>'Farg‘ona davlat universiteti','structureType'=>['code'=>'10','name'=>'Universitet']],
 ['id'=>10,'name'=>'Chet tillari','parent'=>1,'structureType'=>['code'=>'11','name'=>'Fakultet']],
 ['id'=>150,'name'=>'Amaliy ingliz tili kafedrasi','parent'=>['id'=>10],'structureType'=>['code'=>'12','name'=>'Kafedra']],
 ['id'=>151,'name'=>'Adabiyotshunoslik','parent'=>10,'structureType'=>['code'=>'12','name'=>'Кафедра']],
 ['id'=>200,'name'=>'Buxgalteriya hisobi va moliya','parent'=>1,'structureType'=>['code'=>'13','name'=>'Bo‘lim']],
 ['id'=>201,'name'=>'Axborot texnologiyalari markazi','parent'=>1,'structureType'=>['code'=>'14','name'=>'Markaz']],
 ['id'=>202,'name'=>'Yangi tuzilma','parent'=>1,'structureType'=>['code'=>'99']],
];
$result=Hemis::applyStructure($items,$root);
$faculty=(new Query())->from('org')->where(['hemis_id'=>'10'])->one();$department=(new Query())->from('org')->where(['hemis_id'=>'150'])->one();$office=(new Query())->from('org')->where(['hemis_id'=>'200'])->one();
check($faculty['kind']==='faculty' && $department['kind']==='department' && $office['kind']==='unit','11/12/13 codes classified by actual labels, not hardcoded mapping');
check((int)$department['id']===$wrong && (int)$department['parent_id']===(int)$faculty['id'] && (int)User::findOne($teacher->id)->org_id===$wrong,'reimport repairs faculty link without replacing org IDs or employee links');
check((new Query())->from('org')->where(['id'=>$plain])->one()['kind']==='department','plain legacy name corrected using API type and parent');
check((new Query())->from('org')->where(['hemis_id'=>'202'])->one()['type_verified']==0,'unknown API type stays in review and never becomes a faculty');
check($result['faculties']===1 && $result['departments']===2 && $result['units']===3,'import reports separate academic and non-academic counts');
check(Access::employeeIds($badFacultyRole)===[$badFacultyRole->id],'incorrect faculty-role assignment cannot gain broader visibility after correction');
$before=(int)(new Query())->from('org')->count();Hemis::applyStructure($items,$root);check((int)(new Query())->from('org')->count()===$before,'classification repair is idempotent');
$unit=(int)$office['id'];Organization::saveDetails($super,['name'=>$office['name'],'parent_id'=>$root,'manual_structure'=>1],$unit,'department');Hemis::applyStructure($items,$root);
check((new Query())->from('org')->where(['id'=>$unit])->one()['kind']==='department','explicit superadmin classification survives reimport');
$unknown=(new Query())->from('org')->where(['hemis_id'=>'202'])->one();Organization::saveDetails($super,['name'=>'O‘quv bo‘limi','parent_id'=>$root],(int)$unknown['id'],'unit');
check((new Query())->from('org')->where(['id'=>$unknown['id']])->one()['type_verified']==1,'manual confirmation clears classification review');
$map=OrganizationClassifier::classify([1=>['name'=>'Fizika','structureType'=>['code'=>'99','name'=>'Fakultet']],2=>['name'=>'Matematika','structureType'=>['code'=>'99','name'=>'Kafedra']]]);
check($map[1]['kind']==='faculty' && $map[2]['kind']==='department','same numeric code cannot override explicit different type names');
$map=OrganizationClassifier::classify([1=>['name'=>'Fakultet dekanati','structureType'=>['name'=>'Bo‘lim']],2=>['name'=>'Farg‘ona davlat universiteti ingliz tili','structureType'=>['name'=>'Kafedra']]]);
check($map[1]['kind']==='unit' && $map[2]['kind']==='department','institution prefixes and dean offices do not create fake faculties');
echo "SUCCESS: $count classification repair checks.\n";
