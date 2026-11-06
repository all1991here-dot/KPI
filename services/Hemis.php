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
        $token=trim(preg_replace('/^Bearer\s+/i','',trim($token)));
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
        $path=Yii::getAlias('@runtime/hemis.json');
        $temporary=tempnam(dirname($path),'hemis-');
        if($temporary===false)throw new DomainException('HEMIS sozlamalarini saqlash uchun runtime papkasiga yozish ruxsati kerak.');
        try {
            chmod($temporary,0600);
            $contents=json_encode(['host'=>$host,'token'=>$encrypted],JSON_THROW_ON_ERROR);
            if(file_put_contents($temporary,$contents,LOCK_EX)!==strlen($contents) || !rename($temporary,$path))throw new DomainException('HEMIS sozlamalari saqlanmadi. Avvalgi sozlamalar saqlangan holatda qoldi.');
        } finally { if(is_file($temporary))unlink($temporary); }
    }
    public static function fetch(string $kind): array
    {
        $s=self::settings();if(empty($s['host']) || empty($s['token']))throw new DomainException('HEMIS domeni va API kaliti sozlanmagan.');
        $token=Yii::$app->security->decryptByKey(base64_decode($s['token']),Yii::$app->params['cookieKey']);if(!$token)throw new DomainException('API kalitini qayta saqlang.');$token=trim(preg_replace('/^Bearer\s+/i','',$token));
        if(!in_array($kind,['employees','structure'],true))throw new DomainException('Import turi noto‘g‘ri.');
        $path=$kind==='employees'?'employee-list?type=all&':'department-list?';$rows=[];$seen=[];
        for($page=1;$page<=200;$page++){
            $url='https://'.$s['host'].'/rest/v1/data/'.$path.'limit=200&page='.$page;
            $json=HemisHttp::get($url,$token);[$items,$total]=HemisPayload::page($json);
            if(!$items)return $rows;
            $fingerprint=hash('sha256',json_encode($items,JSON_THROW_ON_ERROR));
            if(isset($seen[$fingerprint]))throw new DomainException('HEMIS bir xil sahifani takror qaytardi. Sahifalashni HEMIS administratori bilan tekshiring; import qo‘llanmadi.');
            $seen[$fingerprint]=true;array_push($rows,...$items);
            if($total!==null && $page>=$total)return $rows;
        }
        throw new DomainException('Import 200 sahifadan oshdi; natija qo‘llanmadi.');
    }
    public static function applyStructure(array $items,?int $universityId=null): array
    {
        return Yii::$app->db->transaction(function()use($items,$universityId){
            $roots=(new Query())->from('org')->where(['kind'=>'university'])->all();
            $university=$universityId?(new Query())->from('org')->where(['id'=>$universityId,'kind'=>'university'])->one():(count($roots)===1?$roots[0]:null);
            if(!$university || trim($university['name'])==='Universitet')throw new DomainException('Avval universitet nomini qolda toldiring va import uchun universitetni tanlang.');
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
                    if(!$old){$manual=(new Query())->from('org')->where(['name'=>$name,'kind'=>$kind,'parent_id'=>$parent,'hemis_id'=>null])->all();if(count($manual)>1){$result['skipped']++;continue;}if(count($manual)===1)$old=$manual[0];}
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
        $schema=Yii::$app->db->schema->getTableSchema('user',true);
        if(!isset($schema->columns['hemis_profile'],$schema->columns['deleted_at']))throw new DomainException('Xodim profili bazasi yangilanmagan. KPI qora oynasini yoping va start-native.bat bilan qayta oching; migratsiya bajarilishini kuting.');
        if(!$items)throw new DomainException('HEMIS 0 ta xodim qaytardi. API backend domeni va employee-list ruxsatini tekshiring. Mavjud hisoblar o‘zgarmadi.');
        return Yii::$app->db->transaction(function()use($items){
            $result=['received'=>count($items),'saved'=>0,'created'=>0,'updated'=>0,'skipped'=>0,'unlinked'=>0,'reasons'=>[]];$initialHash=null;
            foreach($items as $raw){
                $item=HemisPayload::employee($raw);
                $id=self::id($item['id']??null);$name=$item['full_name']??'';
                if(!$id || $name===''){$result['skipped']++;$reason=!$id?'HEMIS ID yo‘q':'F.I.Sh. yo‘q';$result['reasons'][$reason]=($result['reasons'][$reason]??0)+1;continue;}
                $department=self::id($item['department']??$item['_department']??$item['department_id']??null);$org=$department?(new Query())->from('org')->where(['hemis_id'=>$department])->one():null;
                $user=User::findOne(['hemis_id'=>$id]);$new=!$user;
                if($user && $user->deleted_at){$result['skipped']++;$result['reasons']['Superadministrator o‘chirgan hisob']=($result['reasons']['Superadministrator o‘chirgan hisob']??0)+1;continue;}
                if($new){$user=new User();$user->username='hemis_'.$id;if(User::findOne(['username'=>$user->username])){$result['skipped']++;$result['reasons']['Login band, HEMIS ID bilan bog‘lang']=($result['reasons']['Login band, HEMIS ID bilan bog‘lang']??0)+1;continue;}$user->role='unassigned';$user->active=0;$user->must_change_password=1;$user->created_at=time();
                    // All imported accounts are inactive. Generate one unknown password hash per batch,
                    // rather than hundreds of expensive hashes that can exhaust a web request timeout.
                    $initialHash??=Yii::$app->security->generatePasswordHash(Yii::$app->security->generateRandomString(64));$user->password_hash=$initialHash;$user->auth_key=Yii::$app->security->generateRandomString(32);$user->hemis_id=$id;}

                $user->name=mb_substr($name,0,200);$user->org_id=$org?$org['id']:null;
                $result['unlinked']=($result['unlinked']??0)+($org?0:1);
                $user->degree=self::text($item['academicDegree']??$item['academic_degree']??null,50);
                $user->position=self::text($item['staffPosition']??$item['position']??$item['employeeType']??null,100);
                $profile=[
                    'photo_url'=>EmployeePhoto::fromItem($item),
                    'academic_rank'=>self::text($item['academicRank']??$item['academic_rank']??null),
                    'employment_form'=>self::text($item['employmentForm']??$item['employment_form']??null),
                    'employment_staff'=>self::text($item['employmentStaff']??$item['employment_staff']??$item['rate']??null),
                    'employee_type'=>self::text($item['employeeType']??$item['employee_type']??null),
                    'phone'=>self::text($item['phone']??$item['phone_number']??null),
                    'email'=>self::text($item['email']??null),
                ];
                $user->hemis_profile=json_encode($profile,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
                $user->save(false);$result['saved']++;$result[$new?'created':'updated']++;
            }
            return $result;
        });
    }
    private static function text($value,int $max=200): ?string
    {
        if(is_array($value))$value=$value['name']??$value['value']??null;
        if(!is_scalar($value))return null;
        $value=trim((string)$value);return $value===''?null:mb_substr($value,0,$max);
    }
    private static function id($value): ?string
    {
        $id=is_array($value)?($value['id']??null):$value;return (is_string($id) || is_int($id)) && preg_match('/^[0-9]{1,40}$/',(string)$id)?(string)$id:null;
    }
}
