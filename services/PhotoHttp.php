<?php
namespace app\services;
use DomainException;
/** Restricted image download; bearer credentials never leave the configured HEMIS host. */
class PhotoHttp
{
    public static function get(string $url): string
    {
        $url=EmployeePhoto::url($url);if(!$url)throw new DomainException('Rasm manzili noto‘g‘ri.');
        $host=strtolower(parse_url($url,PHP_URL_HOST));$settings=Hemis::settings();$trusted=$host===strtolower($settings['host']??'');
        $headers=['Accept: image/jpeg, image/png, image/webp'];$resolved=[];
        if(!$trusted){
            $ips=gethostbynamel($host)?:[];foreach($ips as $ip)if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))$resolved[]=$ip;
            if(!$resolved)throw new DomainException('Rasm serveri manzili ruxsat etilmagan.');
        }elseif(!empty($settings['token'])){
            $token=\Yii::$app->security->decryptByKey(base64_decode($settings['token']),\Yii::$app->params['cookieKey']);
            if($token)$headers[]='Authorization: Bearer '.trim(preg_replace('/^Bearer\s+/i','',$token));
        }
        $curl=curl_init($url);$data='';
        try{
            curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>5,CURLOPT_HTTPHEADER=>$headers,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
            if($resolved)curl_setopt($curl,CURLOPT_RESOLVE,[$host.':443:'.$resolved[0]]);
            $ca=getenv('KPI_CA_BUNDLE');if($ca && is_file($ca))curl_setopt($curl,CURLOPT_CAINFO,$ca);
            elseif(PHP_OS_FAMILY==='Windows'){curl_setopt($curl,CURLOPT_CAINFO,dirname(__DIR__).'/resources/tls/cacert.pem');if(defined('CURLSSLOPT_NATIVE_CA'))curl_setopt($curl,CURLOPT_SSL_OPTIONS,CURLSSLOPT_NATIVE_CA);}
            curl_setopt($curl,CURLOPT_WRITEFUNCTION,static function($handle,$chunk)use(&$data){if(strlen($data)+strlen($chunk)>3*1024*1024)return 0;$data.=$chunk;return strlen($chunk);});
            if(curl_exec($curl)===false || (int)curl_getinfo($curl,CURLINFO_HTTP_CODE)!==200)throw new DomainException('Rasm olinmadi.');
            return $data;
        }finally{curl_close($curl);}
    }
}
