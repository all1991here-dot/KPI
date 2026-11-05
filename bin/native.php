<?php
// Local-only launcher. No downloads and no changes to the user's global php.ini.
$root=dirname(__DIR__);chdir($root);
if(version_compare(PHP_VERSION,'8.3.0','<')){fwrite(STDERR,"PHP 8.3 yoki yangiroq kerak. Hozir: ".PHP_VERSION."\n");exit(1);}
if(!is_file($root.'/vendor/autoload.php')){fwrite(STDERR,"vendor papkasi yo‘q. To‘liq KPI ZIP paketini oching.\n");exit(1);}
$required=['pdo','pdo_sqlite','mbstring','intl','curl','fileinfo','zip','dom','xml','simplexml','ctype','openssl'];
$prepared=in_array('--prepared',$argv,true);$ini=$root.'/runtime/php-native.ini';
if(!is_dir($root.'/runtime'))mkdir($root.'/runtime',0700,true);
if(PHP_OS_FAMILY==='Windows' && !$prepared){
    $extensions=str_replace('\\','/',dirname(PHP_BINARY).'/ext');
    $contents="[PHP]\ndate.timezone=Asia/Tashkent\nmemory_limit=256M\nupload_max_filesize=10M\npost_max_size=110M\nmax_file_uploads=10\ndisplay_errors=Off\nlog_errors=On\n";
    $contents.='extension_dir="'.$extensions."\"\n";
    $contents.='error_log="'.str_replace('\\','/',$root)."/runtime/php-native-errors.log\"\n";
    foreach($required as $module){$dll='php_'.$module.'.dll';if(is_file($extensions.'/'.$dll))$contents.='extension='.$dll."\n";}
    file_put_contents($ini,$contents);@chmod($ini,0600);
    $process=proc_open(array_merge([PHP_BINARY,'-c',$ini,__FILE__,'--prepared'],in_array('--smoke',$argv,true)?['--smoke']:[]),[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes,$root);
    if(!is_resource($process)){fwrite(STDERR,"PHP ishga tushmadi.\n");exit(1);}exit(proc_close($process));
}
$missing=array_values(array_filter($required,fn($name)=>!extension_loaded($name)));
if($missing){fwrite(STDERR,"PHP kengaytmalari yetishmayapti: ".implode(', ',$missing)."\nWindows PHP ZIP ichidagi ext papkasi ham to‘liq ochilgan bo‘lsin.\n");exit(1);}
require $root.'/vendor/autoload.php';require $root.'/vendor/yiisoft/yii2/Yii.php';Yii::setAlias('@app',$root);
$config=require $root.'/config/console.php';
if(!is_dir($config['runtimePath']))mkdir($config['runtimePath'],0700,true);
$app=new yii\console\Application($config);
$result=$app->runAction('setup/init');if($result)exit($result);$app->db->close();
$port=null;
for($candidate=8000;$candidate<=8010;$candidate++){
    $socket=@stream_socket_server('tcp://127.0.0.1:'.$candidate,$errno,$error);
    if($socket){fclose($socket);$port=$candidate;break;}
}
if($port===null){fwrite(STDERR,"8000–8010 portlari band. Keraksiz xizmatlarni to‘xtating.\n");exit(1);}
$command=[PHP_BINARY];if(PHP_OS_FAMILY==='Windows')array_push($command,'-c',$ini);
array_push($command,'-S','127.0.0.1:'.$port,'-t',$root.'/web',$root.'/web/router.php');
$server=proc_open($command,[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes,$root);
if(!is_resource($server)){fwrite(STDERR,"Web xizmatini ochib bo‘lmadi.\n");exit(1);}
$url='http://127.0.0.1:'.$port.'/?r=auth/login';$ready=false;
$context=stream_context_create(['http'=>['timeout'=>1]]);
for($attempt=0;$attempt<30;$attempt++){
    $html=@file_get_contents($url,false,$context);
    if($html!==false && str_contains($html,'Tizimga kirish')){$ready=true;break;}
    if(!proc_get_status($server)['running'])break;
    usleep(200000);
}
if(!$ready){proc_terminate($server);proc_close($server);fwrite(STDERR,"Xizmat tayyor bo‘lmadi. runtime/php-native-errors.log ni tekshiring.\n");exit(1);}
echo "\nKPI tayyor: ".$url."\nOynani ochiq qoldiring. To‘xtatish: Ctrl+C.\n";
if(is_file($root.'/runtime/initial-admin.txt'))echo "Birinchi login/parol: runtime/initial-admin.txt faylida.\n";
if(PHP_OS_FAMILY==='Windows')pclose(popen('start "" "'.$url.'"','r'));
// A diagnostic mode lets automated smoke checks stop only their own child server.
if(in_array('--smoke',$argv,true)){proc_terminate($server);proc_close($server);echo "Native startup smoke check passed.\n";exit(0);}
exit(proc_close($server));
