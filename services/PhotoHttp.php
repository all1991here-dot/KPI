<?php
namespace app\services;
use DomainException;
/** Download HTTPS photos; credentials are rebuilt for each redirect host. */
class PhotoHttp
{
    public static function get(string $url): string
    {
        if(!extension_loaded('curl'))throw new DomainException('PHP cURL kengaytmasi yoqilmagan.');
        $settings=Hemis::settings();$visited=[];
        for($hop=0;$hop<=3;$hop++){
            $url=EmployeePhoto::url($url);if(!$url)throw new DomainException('Rasm manzili noto‘g‘ri.');
            if(isset($visited[$url]))throw new DomainException('Rasm serveri takroriy yo‘naltirmoqda.');$visited[$url]=true;
            $host=strtolower(parse_url($url,PHP_URL_HOST));$trusted=$host===strtolower($settings['host']??'');
            $headers=['Accept: image/jpeg, image/png, image/webp'];$resolved=[];
            if(!$trusted){
                foreach(gethostbynamel($host)?:[] as $ip)if(filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))$resolved[]=$ip;
                if(!$resolved)throw new DomainException('Rasm serverining DNS manzili topilmadi yoki tashqi manzilga ruxsat yo‘q.');
            }elseif(!empty($settings['token'])){
                $token=\Yii::$app->security->decryptByKey(base64_decode($settings['token']),\Yii::$app->params['cookieKey']);
                if($token)$headers[]='Authorization: Bearer '.trim(preg_replace('/^Bearer\s+/i','',$token));
            }
            $curl=curl_init($url);$data='';$location=null;$tooLarge=false;
            try{
                curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,CURLOPT_HTTPHEADER=>$headers,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'FarDU-KPI/1.2']);
                if($resolved)curl_setopt($curl,CURLOPT_RESOLVE,[$host.':443:'.$resolved[0]]);
                $ca=getenv('KPI_CA_BUNDLE');
                if($ca && (!is_file($ca) || !is_readable($ca)))throw new DomainException('KPI_CA_BUNDLE sertifikat fayli topilmadi.');
                if($ca)curl_setopt($curl,CURLOPT_CAINFO,$ca);
                elseif(PHP_OS_FAMILY==='Windows'){
                    $bundle=dirname(__DIR__).'/resources/tls/cacert.pem';if(!is_file($bundle))throw new DomainException('Rasm uchun SSL sertifikat paketi topilmadi. Yangilanishni to‘liq o‘rnating.');
                    curl_setopt($curl,CURLOPT_CAINFO,$bundle);if(defined('CURLSSLOPT_NATIVE_CA'))curl_setopt($curl,CURLOPT_SSL_OPTIONS,CURLSSLOPT_NATIVE_CA);
                }
                curl_setopt($curl,CURLOPT_HEADERFUNCTION,static function($handle,$line)use(&$location){
                    if(str_starts_with($line,'HTTP/'))$location=null;
                    if(strncasecmp($line,'Location:',9)===0)$location=trim(substr($line,9));return strlen($line);
                });
                curl_setopt($curl,CURLOPT_WRITEFUNCTION,static function($handle,$chunk)use(&$data,&$tooLarge){if(strlen($data)+strlen($chunk)>3*1024*1024){$tooLarge=true;return 0;}$data.=$chunk;return strlen($chunk);});
                $ok=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);
                if($tooLarge)throw new DomainException('Rasm hajmi 3 MB chegaradan oshdi.');
                if($ok===false){$code=curl_errno($curl);$reason=match($code){5,6=>'Rasm serveri DNS orqali topilmadi.',7=>'Rasm serveriga ulanib bo‘lmadi.',28=>'Rasm serveri javob berish vaqti tugadi.',35,60=>'Rasm serverining SSL sertifikati tasdiqlanmadi. Kompyuter sanasi va sertifikatlarni tekshiring.',77=>'Rasm uchun CA sertifikat fayli o‘qilmadi.',default=>'Rasm yuklashda tarmoq xatosi.'};throw new DomainException($reason.' (cURL '.$code.').');}
                if(in_array($status,[301,302,303,307,308],true)){
                    if(!$location || $hop===3)throw new DomainException('Rasm serverining yo‘naltirish chegarasi yoki manzili noto‘g‘ri.');
                    $url=EmployeePhoto::redirectUrl($location,$url);if(!$url)throw new DomainException('Rasm serveri ruxsat etilmagan manzilga yo‘naltirdi.');continue;
                }
                if($status!==200)throw new DomainException(match($status){401,403=>'HEMIS rasmga kirishni rad etdi. Rasm uchun API ruxsatini tekshiring.',404=>'HEMIS rasm faylini topmadi. Xodimlarni qayta import qiling.',default=>'Rasm serveridan javob olinmadi.'}.' (HTTP '.$status.').');
                return $data;
            }finally{curl_close($curl);}
        }
        throw new DomainException('Rasm olinmadi.');
    }
}
