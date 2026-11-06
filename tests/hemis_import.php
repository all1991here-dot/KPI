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
foreach(['m261105_000001_initial','m261106_000001_employee_profile','m261106_000002_superadmin_management','m261106_000003_organization_details'] as $migration){require dirname(__DIR__).'/migrations/'.$migration.'.php';ob_start();(new $migration())->up();ob_end_clean();}\Yii::$app->db->schema->refresh();
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
check((int)\app\models\User::find()->where(['active'=>1])->count()===0,'bulk-created accounts remain inactive');
$authKeys=\app\models\User::find()->select('auth_key')->where(['hemis_id'=>array_column($batch,'id')])->column();check(count(array_unique($authKeys))===250,'each imported account has independent authentication key');
$again=Hemis::applyEmployees($batch);check($again['created']===0 && $again['updated']===250,'bulk reimport updates without duplicates');
echo "SUCCESS: $count HEMIS import checks.\n";
