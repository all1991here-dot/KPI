<?php
namespace app\services;
use Yii;
use DomainException;
use yii\db\Query;
use app\models\User;
class Hemis
{
    public static function settings(): array
    {
        $path=Yii::getAlias('@runtime/hemis.json');return is_file($path)?(json_decode(file_get_contents($path),true)?:[]):[];
    }
    public static function configure(string $host,string $token): void
    {
        $host=strtolower(trim($host));$host=preg_replace('~^https://~','',$host);$host=rtrim($host,'/');
        if(!preg_match('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,63}$/',$host))throw new DomainException('HEMIS uchun HTTPS domen nomini kiriting, yo‘l yoki port kiritmang.');
        $old=self::settings();
        if($token===''){
            if(($old['host']??'')!==$host || empty($old['token']))throw new DomainException('Yangi domen uchun API kaliti kerak.');
            $encrypted=$old['token'];
        }else{
            if(strlen($token)>4096 || preg_match('/[\r\n]/',$token))throw new DomainException('API kaliti formati noto‘g‘ri.');
            $encrypted=base64_encode(Yii::$app->security->encryptByKey($token,Yii::$app->params['cookieKey']));
        }
        $path=Yii::getAlias('@runtime/hemis.json');file_put_contents($path,json_encode(['host'=>$host,'token'=>$encrypted]));chmod($path,0600);
    }
    public static function fetch(string $kind): array
    {
        $s=self::settings();if(empty($s['host']) || empty($s['token']))throw new DomainException('HEMIS domeni va API kaliti sozlanmagan.');
        $token=Yii::$app->security->decryptByKey(base64_decode($s['token']),Yii::$app->params['cookieKey']);if(!$token)throw new DomainException('API kalitini qayta saqlang.');
        $path=$kind==='employees'?'employee-list?type=all&':'department-list?';$rows=[];
        for($page=1;$page<=200;$page++){
            $url='https://'.$s['host'].'/rest/v1/data/'.$path.'limit=200&page='.$page;
            $curl=curl_init($url);curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>45,CURLOPT_CONNECTTIMEOUT=>15,CURLOPT_HTTPHEADER=>['Accept: application/json','Authorization: Bearer '.$token],CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
            $ca=getenv('KPI_CA_BUNDLE');if($ca && is_file($ca))curl_setopt($curl,CURLOPT_CAINFO,$ca);
            $data='';curl_setopt($curl,CURLOPT_WRITEFUNCTION,function($c,$chunk)use(&$data){if(strlen($data)+strlen($chunk)>8*1024*1024)return 0;$data.=$chunk;return strlen($chunk);});
            $ok=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
            if($ok===false || $status!==200)throw new DomainException('HEMIS javobi olinmadi (HTTP '.$status.'). Domen ruxsati va API kalitini tekshiring.');
            $json=json_decode($data,true,64,JSON_THROW_ON_ERROR);if(isset($json['success']) && !$json['success'])throw new DomainException('HEMIS so‘rovni rad etdi.');
            $items=$json['data']['items']??$json['items']??null;if(!is_array($items))throw new DomainException('HEMIS javob tuzilmasi kutilgan formatda emas.');
            array_push($rows,...$items);$total=$json['data']['pagination']['pageCount']??$json['pagination']['pageCount']??null;
            if(!$items || ($total!==null && $page>=(int)$total) || ($total===null && count($items)<200))return $rows;
        }
        throw new DomainException('Import 200 sahifadan oshdi; natija qo‘llanmadi.');
    }
    public static function applyStructure(array $items): array
    {
        return Yii::$app->db->transaction(function()use($items){
            $university=(new Query())->from('org')->where(['kind'=>'university'])->one();if(!$university)throw new DomainException('Avval universitet yarating.');
            $map=[];$result=['saved'=>0,'skipped'=>0];
            foreach($items as $item){$id=self::id($item['id']??null);if($id)$map[$id]=$item;}
            foreach(['12'=>'faculty','13'=>'department'] as $code=>$kind){
                foreach($map as $id=>$item){
                    $type=$item['structureType']??$item['structure_type']??[];$typeCode=(string)(is_array($type)?($type['code']??$type['id']??''):$type);
                    if($typeCode!==(string)$code)continue;
                    $name=trim((string)($item['name']??''));if(!$name){$result['skipped']++;continue;}
                    $parent=$university['id'];
                    if($kind==='department'){
                        $parentHemis=self::id($item['parent']??null);$seen=[];$faculty=null;
                        while($parentHemis && !isset($seen[$parentHemis])){
                            $seen[$parentHemis]=true;$faculty=(new Query())->from('org')->where(['hemis_id'=>$parentHemis,'kind'=>'faculty'])->one();if($faculty)break;$parentHemis=self::id($map[$parentHemis]['parent']??null);
                        }
                        if(!$faculty){$result['skipped']++;continue;}$parent=$faculty['id'];
                    }
                    $old=(new Query())->from('org')->where(['hemis_id'=>(string)$id])->one();
                    if($old && $old['kind']!==$kind){$result['skipped']++;continue;}
                    $values=['hemis_id'=>(string)$id,'name'=>mb_substr($name,0,200),'kind'=>$kind,'parent_id'=>$parent];$command=Yii::$app->db->createCommand();
                    if($old)$command->update('org',$values,['id'=>$old['id']])->execute();else $command->insert('org',$values)->execute();$result['saved']++;
                }
            }
            return $result;
        });
    }
    public static function applyEmployees(array $items): array
    {
        return Yii::$app->db->transaction(function()use($items){
            $result=['saved'=>0,'skipped'=>0];
            foreach($items as $item){
                $id=self::id($item['id']??null);$name=trim((string)($item['full_name']??$item['fullName']??$item['name']??''));
                if(!$name)$name=trim(implode(' ',[$item['second_name']??'',$item['first_name']??'',$item['third_name']??'']));
                if(!$id || !$name){$result['skipped']++;continue;}
                $department=self::id($item['department']??$item['department_id']??null);$org=$department?(new Query())->from('org')->where(['hemis_id'=>$department])->one():null;
                $user=User::findOne(['hemis_id'=>$id]);$new=!$user;
                if($new){$user=new User();$user->username='hemis_'.$id;if(User::findOne(['username'=>$user->username])){$result['skipped']++;continue;}$user->role='teacher';$user->active=0;$user->must_change_password=1;$user->created_at=time();$user->setPassword(Yii::$app->security->generateRandomString(48));$user->hemis_id=$id;}
                $user->name=mb_substr($name,0,200);if($org)$user->org_id=$org['id'];
                $degree=$item['academicDegree']??$item['academic_degree']??[];$position=$item['staffPosition']??$item['position']??[];
                $user->degree=mb_substr(is_array($degree)?(string)($degree['name']??''):(string)$degree,0,50);$user->position=mb_substr(is_array($position)?(string)($position['name']??''):(string)$position,0,100);
                $user->save(false);$result['saved']++;
            }
            return $result;
        });
    }
    private static function id($value): ?string
    {
        $id=is_array($value)?($value['id']??null):$value;return $id!==null && preg_match('/^[0-9]{1,40}$/',(string)$id)?(string)$id:null;
    }
}
