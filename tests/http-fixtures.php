<?php
// Fixture accounts are created ONLY in an explicitly selected isolated test runtime.
if(!getenv('KPI_RUNTIME_DIR') || !str_contains(getenv('KPI_RUNTIME_DIR'),'/http-test-')){fwrite(STDERR,"Isolated test runtime required.\n");exit(1);}
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';Yii::setAlias('@app',dirname(__DIR__));
$config=require dirname(__DIR__).'/config/console.php';@mkdir($config['runtimePath'],0700,true);new yii\console\Application($config);
require dirname(__DIR__).'/migrations/m261105_000001_initial.php';ob_start();$ok=(new m261105_000001_initial())->up();ob_end_clean();if($ok===false)exit(1);require dirname(__DIR__).'/migrations/m261106_000001_employee_profile.php';ob_start();(new m261106_000001_employee_profile())->up();ob_end_clean();require dirname(__DIR__).'/migrations/m261106_000002_superadmin_management.php';ob_start();(new m261106_000002_superadmin_management())->up();ob_end_clean();require dirname(__DIR__).'/migrations/m261106_000003_organization_details.php';ob_start();(new m261106_000003_organization_details())->up();ob_end_clean();Yii::$app->db->schema->refresh();
app\services\Catalog::import();$db=Yii::$app->db;
$db->createCommand()->insert('org',['name'=>'Test University','kind'=>'university'])->execute();$u=(int)$db->lastInsertID;
$db->createCommand()->insert('org',['name'=>'Test Faculty','kind'=>'faculty','parent_id'=>$u])->execute();$f=(int)$db->lastInsertID;
$db->createCommand()->insert('org',['name'=>'Test Department','kind'=>'department','parent_id'=>$f])->execute();$d=(int)$db->lastInsertID;
foreach(['teacher'=>'teacher','other'=>'teacher','head'=>'head','expert'=>'expert','admin'=>'superadmin','manager'=>'management'] as $name=>$role){$user=new app\models\User();$user->username='http_'.$name;$user->name='HTTP '.$name;$user->role=$role;$user->category='PHD';$user->org_id=$d;$user->active=1;$user->must_change_password=0;$user->created_at=time();$user->setPassword('OnlyForIsolated-HttpTests2026!');$user->save(false);}
$db->createCommand()->insert('period',['name'=>'HTTP test 2026','starts'=>'2026-01-01','ends'=>'2026-12-31','closed'=>0])->execute();app\services\Catalog::createRules((int)$db->lastInsertID);
foreach([[1,['head']],[2,['expert']],[3,['superadmin']]] as [$step,$roles])$db->createCommand()->insert('route_step',['position'=>$step,'name'=>'Test step '.$step,'roles'=>json_encode($roles)])->execute();
echo "HTTP fixtures ready.\n";
