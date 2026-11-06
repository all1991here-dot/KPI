<?php
namespace app\services;
use Yii;
use DomainException;
use app\models\User;
class HemisPhoto
{
    public static function path(int $id,string $url): string
    {
        return Yii::getAlias('@runtime/hemis-photos/').hash('sha256',$id.'|'.$url).'.img';
    }
    public static function store(int $id,string $url,string $data): string
    {
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->buffer($data);$info=@getimagesizefromstring($data);
        if(strlen($data)>3*1024*1024 || !in_array($mime,['image/jpeg','image/png','image/webp'],true) || !$info || $info[0]<1 || $info[1]<1 || $info[0]>10000 || $info[1]>10000 || $info[0]*$info[1]>40000000)throw new DomainException('Rasm fayli yaroqsiz.');
        $path=self::path($id,$url);$dir=dirname($path);if(!is_dir($dir))mkdir($dir,0700,true);
        $temp=tempnam($dir,'photo-');try{chmod($temp,0600);if(file_put_contents($temp,$data)!==strlen($data) || !rename($temp,$path))throw new DomainException('Rasm saqlanmadi.');}finally{if(is_file($temp))unlink($temp);}
        return $path;
    }
    public static function file(User $person,bool $retry=false): string
    {
        $profile=json_decode($person->hemis_profile??'{}',true)?:[];$url=EmployeePhoto::url($profile['photo_url']??null);if(!$url)throw new DomainException('HEMIS rasm manzilini bermagan. Xodimlarni qayta import qiling.');
        $path=self::path((int)$person->id,$url);
        if(!$retry && is_file($path) && filemtime($path)>time()-86400)return $path;
        $miss=$path.'.miss-v2';if(!$retry && is_file($miss) && filemtime($miss)>time()-60){if(is_file($path))return $path;throw new DomainException('Rasm vaqtincha yuklanmadi. Tahrirlash oynasida «Rasmni tekshirish»ni bosing.');}
        try{$file=self::store((int)$person->id,$url,PhotoHttp::get($url));if(is_file($miss))unlink($miss);return $file;}
        catch(\Throwable $e){if(!is_dir(dirname($path)))mkdir(dirname($path),0700,true);touch($miss);if(!$retry && is_file($path))return $path;throw new DomainException($e instanceof DomainException?$e->getMessage():'Rasmni yuklash yoki saqlashda xato.');}
    }
}
