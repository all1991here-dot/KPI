<?php
namespace app\services;
$options=[];$private=false;$responses=[];$requests=[];$scenario=[];
function gethostbynamel($h){global $private;return [$private?'127.0.0.1':'8.8.8.8'];}
function curl_init($u){global $requests,$responses,$scenario;$requests[]=$u;$scenario=array_shift($responses)??['status'=>200,'body'=>'photo fixture'];return new \stdClass();}
function curl_setopt_array($c,$o){global $options;$options=$o;return true;}
function curl_setopt($c,$k,$v){global $options;$options[$k]=$v;return true;}
function curl_exec($c){global $options,$scenario,$requests;$requests[array_key_last($requests)]=['url'=>$requests[array_key_last($requests)],'options'=>$options];if(isset($scenario['location']))($options[CURLOPT_HEADERFUNCTION])($c,'Location: '.$scenario['location']."\r\n");($options[CURLOPT_WRITEFUNCTION])($c,$scenario['body']??'');return !isset($scenario['errno']);}
function curl_errno($c){global $scenario;return $scenario['errno']??0;}
function curl_getinfo($c,$k){global $scenario;return $scenario['status']??200;}
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

$private=false;$responses=[['status'=>302,'location'=>'https://cdn.example.test/photo.png'],['status'=>200,'body'=>'redirected photo']];
check(PhotoHttp::get('https://example.test/photo.png')==='redirected photo','redirected CDN photo downloaded');
check(count(array_filter($options[CURLOPT_HTTPHEADER],fn($h)=>str_starts_with($h,'Authorization:')))===0,'redirect strips bearer on different host');
$responses=[['status'=>301,'location'=>'/static/new.png'],['status'=>200,'body'=>'same host']];
check(PhotoHttp::get('https://example.test/old.png')==='same host' && in_array('Authorization: Bearer Isolated-Photo-Key',$options[CURLOPT_HTTPHEADER],true),'same-host redirect preserves authorized access');
check($requests[array_key_last($requests)]['url']==='https://example.test/static/new.png','root-relative redirect resolved');
$responses=[['status'=>302,'location'=>'/photo.png']];try{PhotoHttp::get('https://example.test/photo.png');throw new \RuntimeException('loop accepted');}catch(\DomainException $e){check(str_contains($e->getMessage(),'takroriy'),'redirect loop reported');}
$responses=[['status'=>403,'body'=>'private token body']];try{PhotoHttp::get('https://example.test/photo.png');throw new \RuntimeException('403 accepted');}catch(\DomainException $e){check(str_contains($e->getMessage(),'HTTP 403') && !str_contains($e->getMessage(),'private token'),'HTTP diagnostic without server body');}
$responses=[['errno'=>60]];try{PhotoHttp::get('https://example.test/photo.png');throw new \RuntimeException('TLS failure accepted');}catch(\DomainException $e){check(str_contains($e->getMessage(),'SSL'),'TLS failure actionable');}
$responses=[['status'=>302,'location'=>'https://private.example.test/secret']];$private=true;try{PhotoHttp::get('https://example.test/photo.png');throw new \RuntimeException('private redirect accepted');}catch(\DomainException $e){check(str_contains($e->getMessage(),'DNS'),'redirect private destination denied');}$private=false;
check(EmployeePhoto::url('cdn.example.test/staff/photo name.png')==='https://cdn.example.test/staff/photo%20name.png','scheme-less host and spaced filename normalized');
check(EmployeePhoto::fromItem(['image'=>null,'employee'=>['image'=>['image_full'=>'/photo.png']]])==='https://example.test/photo.png','nested employee image retained');
check(EmployeePhoto::redirectUrl('../photo.png','https://example.test/static/crop/old.png')==='https://example.test/static/crop/../photo.png','path-relative redirect resolved');
$responses=[['status'=>200,'body'=>file_get_contents(dirname(__DIR__).'/web/images/fardu-logo.png')]];
$person=new \app\models\User();$person->id=900;$person->hemis_profile=json_encode(['photo_url'=>'https://example.test/photo.png']);
$path=HemisPhoto::path(900,'https://example.test/photo.png');if(!is_dir(dirname($path)))mkdir(dirname($path),0700,true);touch($path.'.miss-v2');
check(is_file(HemisPhoto::file($person,true)) && !is_file($path.'.miss-v2'),'manual retry bypasses and clears previous negative cache');
$responses=[['status'=>404]];try{HemisPhoto::file($person,true);throw new \RuntimeException('retry swallowed error');}catch(\DomainException $e){check(str_contains($e->getMessage(),'HTTP 404'),'diagnostic preserves download error');}
check(is_file(HemisPhoto::file($person)),'last valid image survives failed refresh');
