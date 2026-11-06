<?php
namespace app\services;
// Script official API responses; no real API key or personal data is used.
class HemisHttp
{
    public static array $responses=[];
    public static array $urls=[];
    public static function get(string $url,string $token): array
    {
        self::$urls[]=$url;if(!self::$responses)throw new \RuntimeException('Unexpected extra API request');return array_shift(self::$responses);
    }
}
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
\Yii::setAlias('@app',dirname(__DIR__));$dir=dirname(__DIR__).'/runtime/hemis-import-test-'.bin2hex(random_bytes(4));mkdir($dir,0700,true);
$config=require dirname(__DIR__).'/config/console.php';$config['runtimePath']=$dir;$config['components']['db']=['class'=>\yii\db\Connection::class,'dsn'=>'sqlite:'.$dir.'/test.sqlite'];new \yii\console\Application($config);
foreach(['m261105_000001_initial','m261106_000001_employee_profile','m261106_000002_superadmin_management','m261106_000003_organization_details','m261106_000004_organization_types'] as $migration){require dirname(__DIR__).'/migrations/'.$migration.'.php';ob_start();(new $migration())->up();ob_end_clean();}\Yii::$app->db->schema->refresh();
$count=0;function check($condition,$name){global $count;if(!$condition)throw new \RuntimeException($name);echo 'PASS '.$name."\n";$count++;}
function denied($fn,$part){try{$fn();throw new \RuntimeException('Expected failure');}catch(\DomainException $e){check(str_contains($e->getMessage(),$part),$part);}}
Hemis::configure('example.test','Isolated-Test-Key');
HemisHttp::$responses=[['data'=>['items'=>[['id'=>1,'full_name'=>'First']]]],['items'=>[['employee'=>['id'=>2,'first_name'=>'Second','last_name'=>'Employee']]]],['data'=>[]]];
$rows=Hemis::fetch('employees');check(count($rows)===2,'short pages continue until API is empty');check(str_contains(HemisHttp::$urls[1],'page=2'),'next page is requested');
$result=Hemis::applyEmployees($rows);check($result['created']===2 && $result['saved']===2,'flat and nested employees imported end to end');
HemisHttp::$responses=[['data'=>['items'=>[['id'=>3]],'_meta'=>['pageCount'=>1]]]];check(count(Hemis::fetch('employees'))===1,'alternative pagination metadata supported');
HemisHttp::$responses=[['items'=>[['id'=>4]]],['items'=>[['id'=>4]]]];denied(fn()=>Hemis::fetch('employees'),'takror');
HemisHttp::$responses=[['data'=>['unexpected'=>'response']]];denied(fn()=>Hemis::fetch('employees'),'ro‘yxati topilmadi');
denied(fn()=>Hemis::applyEmployees([]),'0 ta xodim');
$batch=[];for($i=1000;$i<1250;$i++)$batch[]=['id'=>$i,'first_name'=>'Batch','last_name'=>'Employee '.$i];
$result=Hemis::applyEmployees($batch);check($result['created']===250,'bulk import creates every employee');
check((int)\app\models\User::find()->where(['hemis_id'=>array_column($batch,'id'),'active'=>1,'role'=>'teacher'])->count()===250,'bulk-created accounts are active teachers');
$authKeys=\app\models\User::find()->select('auth_key')->where(['hemis_id'=>array_column($batch,'id')])->column();check(count(array_unique($authKeys))===250,'each imported account has independent authentication key');
$again=Hemis::applyEmployees($batch);check($again['created']===0 && $again['updated']===250,'bulk reimport updates without duplicates');
$ids=\app\models\User::find()->select('id')->where(['hemis_id'=>array_column($batch,'id')])->column();
\app\models\User::updateAll(['deleted_at'=>time(),'active'=>0],['id'=>$ids]);$skipped=Hemis::applyEmployees($batch,false);check($skipped['saved']===0 && $skipped['skipped']===250,'explicit service skip mode preserves deletions');
$super=new \app\models\User();$super->username='recovery_admin';$super->name='Recovery fixture';$super->role='superadmin';$super->active=1;$super->must_change_password=0;$super->created_at=time();$super->setPassword('OnlyForIsolated-RecoveryTest2026!');$super->save(false);
$recovered=Hemis::applyEmployees($batch,actor:$super);$afterIds=\app\models\User::find()->select('id')->where(['hemis_id'=>array_column($batch,'id'),'deleted_at'=>null])->column();sort($ids);sort($afterIds);
check($recovered['restored']===250 && $recovered['unique_users']===250 && $ids===$afterIds,'explicit recovery restores all 250 original IDs without duplicates');
check((int)\app\models\User::find()->where(['hemis_id'=>array_column($batch,'id'),'active'=>1,'role'=>'teacher'])->count()===250,'recovered batch is active with teacher roles');
$pending=\app\models\User::findOne(['hemis_id'=>1000]);$pending->active=0;$pending->role='unassigned';$pending->save(false);
$blocked=\app\models\User::findOne(['hemis_id'=>1001]);$blocked->active=0;$blocked->save(false);Catalog::audit((int)$super->id,'user_block','User #'.$blocked->id.' blocked');
$custom=\app\models\User::findOne(['hemis_id'=>1002]);$custom->role='expert';$custom->save(false);
$result=Hemis::applyEmployees($batch,actor:$super);
check($result['activated']===1 && (int)\app\models\User::findOne($pending->id)->active===1,'previous pending imports activate automatically');
check((int)\app\models\User::findOne($blocked->id)->active===0,'explicit manual blocks survive import');
check(\app\models\User::findOne($custom->id)->role==='expert','active custom role survives import');
$custom->deleted_at=time();$custom->active=0;$custom->save(false);Hemis::applyEmployees($batch,actor:$super);
check(\app\models\User::findOne($custom->id)->role==='teacher' && (int)\app\models\User::findOne($custom->id)->active===1,'deleted privileged account restores as active teacher');
Hemis::applyEmployees([['id'=>1000,'full_name'=>'Photo fixture','image_full'=>'/static/photo.png']],actor:$super);
$photo=\app\models\User::findOne($pending->id);$url=json_decode($photo->hemis_profile,true)['photo_url'];
check($url==='https://example.test/static/photo.png','relative API photo resolves against HEMIS');
Hemis::applyEmployees([['id'=>1000,'full_name'=>'Photo fixture']],actor:$super);
$photo->refresh();check(json_decode($photo->hemis_profile,true)['photo_url']===$url,'duplicate employee without photo preserves photo');
$data=file_get_contents(dirname(__DIR__).'/web/images/fardu-logo.png');$path=HemisPhoto::store((int)$photo->id,$url,$data);
check(HemisPhoto::file($photo)===$path && file_get_contents($path)===$data,'downloaded photo is privately cached and reused');
denied(fn()=>HemisPhoto::store((int)$photo->id,$url,'<html>not image</html>'),'yaroqsiz');
echo "SUCCESS: $count HEMIS import checks.\n";
