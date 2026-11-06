<?php
namespace app\services;
class EmployeePhoto
{
    public static function fromItem(array $item,?string $host=null): ?string
    {
        foreach(['image_full','image','photo','image_url','photo_url'] as $key){
            $value=$item[$key]??null;if(is_array($value))$value=$value['url']??$value['full']??null;
            $url=self::url($value,$host);if($url)return $url;
        }
        return null;
    }
    /** Display HTTPS image URLs supplied by HEMIS; never attach the API key. */
    public static function url($value,?string $host=null): ?string
    {
        if(!is_string($value) || trim($value)==='')return null;
        $host=$host??(Hemis::settings()['host']??'');
        $value=trim($value);
        if(str_starts_with($value,'/') && !str_starts_with($value,'//')){if(!$host)return null;$value='https://'.$host.$value;}
        $p=parse_url($value);
        if(!$p || ($p['scheme']??'')!=='https' || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/i',$p['host']??'') || isset($p['user']) || isset($p['pass']) || (isset($p['port']) && $p['port']!==443))return null;
        if(preg_match('/[\x00-\x20]/',$value) || strlen($value)>2048)return null;
        return $value;
    }
}
