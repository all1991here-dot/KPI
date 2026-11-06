<?php
namespace app\services;
$options=[];$private=false;
function gethostbynamel($h){global $private;return [$private?'127.0.0.1':'8.8.8.8'];}
function curl_init($u){return new \stdClass();}
function curl_setopt_array($c,$o){global $options;$options=$o;return true;}
function curl_setopt($c,$k,$v){global $options;$options[$k]=$v;return true;}
function curl_exec($c){global $options;($options[CURLOPT_WRITEFUNCTION])($c,'photo fixture');return true;}
function curl_getinfo($c,$k){return 200;}
function curl_close($c){}
require dirname(__DIR__).'/vendor/autoload.php';require dirname(__DIR__).'/vendor/yiisoft/yii2/Yii.php';
\Yii::setAlias('@app',dirname(__DIR__));$dir=dirname(__DIR__).'/runtime/hemis-import-test-'.bin2hex(random_bytes(4));mkdir($dir,0700,true);
$config=require dirname(__DIR__).'/config/console.php';$config['runtimePath']=$dir;$config['components']['db']=['class'=>\yii\db\Connection::class,'dsn'=>'sqlite:'.$dir.'/test.sqlite'];new \yii\console\Application($config);
foreach(['m261105_000001_initial','m261106_000001_employee_profile','m261106_000002_superadmin_management','m261106_000003_organization_details','m261106_000004_organization_types'] as $migration){require dirname(__DIR__).'/migrations/'.$migration.'.php';ob_start();(new $migration())->up();ob_end_clean();}\Yii::$app->db->schema->refresh();

function check($v,$s){if(!$v)throw new \RuntimeException($s);echo 'PASS '.$s."\n";}
Hemis::configure('example.test','Isolated-Photo-Key');
check(PhotoHttp::get('https://example.test/photo.png')==='photo fixture','photo body downloaded');
check(in_array('Authorization: Bearer Isolated-Photo-Key',$options[CURLOPT_HTTPHEADER],true),'same-host photo receives configured token');
check($options[CURLOPT_SSL_VERIFYPEER] && $options[CURLOPT_SSL_VERIFYHOST]===2 && !$options[CURLOPT_FOLLOWLOCATION],'photo TLS and redirect restrictions');
PhotoHttp::get('https://cdn.example.test/photo.png');
check(count(array_filter($options[CURLOPT_HTTPHEADER],fn($h)=>str_starts_with($h,'Authorization:')))===0,'external photo never receives API token');
check($options[CURLOPT_RESOLVE]===['cdn.example.test:443:8.8.8.8'],'external DNS pinned');
$private=true;try{PhotoHttp::get('https://private.example.test/photo.png');throw new \RuntimeException('private address accepted');}catch(\DomainException $e){check(true,'private external destination denied');}
