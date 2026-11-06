<?php
namespace app\services;
class EmployeePhoto
{
    public static function fromItem(array $item,?string $host=null): ?string
    {
        foreach(['image_full','image','photo','image_url','photo_url','avatar','picture','employee_image','image_path'] as $key){
            $value=$item[$key]??null;if(is_array($value))$value=$value['url']??$value['full']??$value['src']??$value['path']??$value['image_full']??$value['image']??null;
            $url=self::url($value,$host);if($url)return $url;
        }
        foreach(['employee','_employee'] as $key)if(is_array($item[$key]??null)){$url=self::fromItem($item[$key],$host);if($url)return $url;}
        return null;
    }
    public static function redirectUrl(string $location,string $base): ?string
    {
        if(preg_match('~^[a-z][a-z0-9+.-]*:~i',$location) || str_starts_with($location,'//'))return self::url($location);
        $p=parse_url($base);$origin='https://'.($p['host']??'');
        if(str_starts_with($location,'/'))return self::url($origin.$location);
        $path=$p['path']??'/';
        if(str_starts_with($location,'?'))return self::url($origin.$path.$location);
        return self::url($origin.substr($path,0,(int)strrpos($path,'/')+1).$location);
    }
    /** Normalize HTTPS image URLs supplied by HEMIS; credentials are handled separately. */
    public static function url($value,?string $host=null): ?string
    {
        if(!is_string($value) || trim($value)==='')return null;
        $host=$host??(Hemis::settings()['host']??'');
        $value=trim($value);if(preg_match('/[\x00-\x1f\x7f]/',$value))return null;
        $value=str_replace(' ','%20',$value);
        if(preg_match('~^(?:[a-z0-9-]+\.)+[a-z]{2,63}/~i',$value))$value='https://'.$value;
        if(str_starts_with($value,'//'))$value='https:'.$value;
        if(!preg_match('~^[a-z][a-z0-9+.-]*:~i',$value) && !str_starts_with($value,'/'))$value='/'.$value;
        if(str_starts_with($value,'/') && !str_starts_with($value,'//')){if(!$host)return null;$value='https://'.$host.$value;}
        $value=preg_replace('~^http://~i','https://',$value);$p=parse_url($value);
        if(!$p || strtolower($p['scheme']??'')!=='https' || !preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/i',$p['host']??'') || isset($p['user']) || isset($p['pass']) || (isset($p['port']) && $p['port']!==443))return null;
        if(preg_match('/[\x00-\x20]/',$value) || strlen($value)>2048)return null;
        return $value;
    }
}
