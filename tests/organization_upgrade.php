<?php
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';Yii::setAlias('@app',dirname(__DIR__));
$dir=dirname(__DIR__).'/runtime/organization-upgrade-'.bin2hex(random_bytes(5));mkdir($dir,0700,true);
$config=require dirname(__DIR__).'/config/console.php';$config['runtimePath']=$dir;$config['components']['db']=['class'=>yii\db\Connection::class,'dsn'=>'sqlite:'.$dir.'/kpi.sqlite'];$app=new yii\console\Application($config);
if($app->runAction('migrate/to',['m261105_000001_initial','interactive'=>false]))exit(1);
Yii::$app->db->schema->refresh();
Yii::$app->db->createCommand()->insert('org',['name'=>'Legacy university','kind'=>'university'])->execute();$root=(int)Yii::$app->db->lastInsertID;
Yii::$app->db->createCommand()->insert('org',['name'=>'Amaliy ingliz tili kafedrasi','kind'=>'faculty','parent_id'=>$root,'hemis_id'=>'150'])->execute();$legacy=(int)Yii::$app->db->lastInsertID;
Yii::$app->db->createCommand()->insert('org',['name'=>'Fizika','kind'=>'faculty','parent_id'=>$root,'hemis_id'=>'151'])->execute();$uncertain=(int)Yii::$app->db->lastInsertID;
$user=new app\models\User();$user->username='upgrade_user';$user->name='Upgrade fixture';$user->role='superadmin';$user->org_id=$legacy;$user->active=1;$user->must_change_password=0;$user->created_at=time();$user->setPassword('OnlyForIsolated-UpgradeTest!');$user->save(false);$id=$user->id;$password=$user->password_hash;
app\services\Hemis::configure('student.fdu.uz','TestOnly-Saved-Upgrade-Key');$settings=file_get_contents($dir.'/hemis.json');
if($app->runAction('migrate/up',['interactive'=>false]))exit(1);Yii::$app->db->schema->refresh();
$updated=app\models\User::findOne($id);
if(!$updated || $updated->password_hash!==$password || $updated->role!=='superadmin' || !array_key_exists('hemis_profile',$updated->attributes) || file_get_contents($dir.'/hemis.json')!==$settings)throw new RuntimeException('Upgrade lost existing data');
if($app->runAction('migrate/up',['interactive'=>false]))exit(1);
if((new yii\db\Query())->from('org')->where(['id'=>$legacy])->one()['kind']!=='department' || (new yii\db\Query())->from('org')->where(['id'=>$uncertain])->one()['type_verified']!=0 || (int)$updated->org_id!==$legacy)throw new RuntimeException('Legacy classification repair failed');
echo "PASS: legacy database migration and repeated startup preserve account, password, role and encrypted API settings.\n";
