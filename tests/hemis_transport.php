<?php
namespace app\services;
require dirname(__DIR__).'/services/HemisHttp.php';
// Script the cURL boundary to cover error handling without a real key or HEMIS data.
$scenario=[];$options=[];$closed=0;$count=0;
function curl_init($url){return new \stdClass();}
function curl_setopt_array($c,$values){global $options;$options=$values;return true;}
function curl_setopt($c,$key,$value){global $options;$options[$key]=$value;return true;}
function curl_exec($c){global $scenario,$options;($options[CURLOPT_WRITEFUNCTION])($c,$scenario['body']??'');return ($scenario['errno']??0) ? false : true;}
function curl_errno($c){global $scenario;return $scenario['errno']??0;}
function curl_getinfo($c,$key){global $scenario;return $scenario['status']??0;}
function curl_close($c){global $closed;$closed++;}
function check($ok,$name){global $count;if(!$ok)throw new \RuntimeException($name);$count++;}
function expect($case,$text){global $scenario,$closed;$scenario=$case;$before=$closed;try{HemisHttp::get('https://example.test/rest/v1/data/department-list','SECRET-TEST-KEY');throw new \RuntimeException('Expected failure');}catch(\DomainException $e){check(str_contains($e->getMessage(),$text),'error classification '.$text);check(!str_contains($e->getMessage(),'SECRET'),'no credentials or response leak');}check($closed===$before+1,'cURL closed on failure');}
putenv('KPI_CA_BUNDLE');
$scenario=['status'=>200,'body'=>'{"success":true,"data":{"items":[]}}'];
check(HemisHttp::get('https://example.test/','SECRET-TEST-KEY')['success']===true,'valid JSON');
check($options[CURLOPT_SSL_VERIFYPEER]===true && $options[CURLOPT_SSL_VERIFYHOST]===2,'TLS verification enabled');
check($options[CURLOPT_FOLLOWLOCATION]===false && $options[CURLOPT_PROTOCOLS]===CURLPROTO_HTTPS,'no redirect or protocol downgrade');
foreach([6=>'DNS',7=>'ulanib',28=>'vaqti',35=>'SSL/TLS',60=>'SSL sertifikati',77=>'CA sertifikat',56=>'uzildi'] as $errno=>$message)expect(['errno'=>$errno,'body'=>'SECRET-TEST-KEY'],$message);
foreach([401=>'kaliti',403=>'taqiqlandi',404=>'backend',302=>'yonaltirmoqda',429=>'chegarasi',503=>'serverida'] as $status=>$message)expect(['status'=>$status,'body'=>'SECRET-TEST-KEY'],$message);
expect(['status'=>200,'body'=>'<html>SECRET</html>'],'JSON');
expect(['status'=>200,'body'=>'null'],'tuzilmasi');
expect(['status'=>200,'body'=>str_repeat('x',8*1024*1024+1)],'8 MB');
putenv('KPI_CA_BUNDLE=/does-not-exist');expect([],'KPI_CA_BUNDLE');putenv('KPI_CA_BUNDLE');
preg_match_all('~-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----~s',file_get_contents(dirname(__DIR__).'/resources/tls/cacert.pem'),$certs);
check(count($certs[0])>100,'public CA bundle complete');foreach($certs[0] as $cert)check(openssl_x509_read($cert)!==false,'valid X509 root');
echo "PASS: $count HEMIS transport/TLS checks\n";
