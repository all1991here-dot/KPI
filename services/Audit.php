<?php
namespace app\services;
use Yii;
use yii\db\Query;
class Audit
{
    public const LABELS=['login'=>'Tizimga kirish','login_failed'=>'Kirish muvaffaqiyatsiz','logout'=>'Tizimdan chiqish','password_change'=>'Parol yangilandi','password_reset'=>'Parol tiklandi','user_save'=>'Foydalanuvchi saqlandi','user_block'=>'Hisob bloklandi','user_unblock'=>'Hisob faollashtirildi','user_delete'=>'Foydalanuvchi o‘chirildi','bulk_delete_users'=>'Foydalanuvchilar ommaviy o‘chirildi','bulk_delete_structure'=>'Tuzilmalar ommaviy o‘chirildi','org_details_save'=>'Tuzilma saqlandi','org_delete'=>'Tuzilma o‘chirildi','org_block'=>'Tuzilma bloklandi','org_unblock'=>'Tuzilma faollashtirildi','indicator_save'=>'Mezon saqlandi','indicator_delete'=>'Mezon o‘chirildi','period_save'=>'Baholash davri saqlandi','rule_update'=>'Hisoblash qoidasi yangilandi','route_update'=>'Tasdiqlash bosqichlari yangilandi','hemis_test'=>'HEMIS ulanishi tekshirildi','hemis_structure'=>'HEMIS tuzilmasi import qilindi','hemis_employees'=>'HEMIS xodimlari import qilindi','report_export'=>'Hisobot yuklab olindi','hemis_org_save'=>'HEMIS tuzilmasi saqlandi','hemis_user_save'=>'HEMIS xodimi saqlandi','evidence_upload'=>'Dalil biriktirildi','evidence_delete'=>'Dalil o‘chirildi','evidence_download'=>'Dalil yuklab olindi','evidence_download_denied'=>'Dalilga kirish rad etildi','request_post'=>'Amal so‘rovi qayta ishlandi','operation_failed'=>'Amal bajarilmadi','access_denied'=>'Kirish huquqi rad etildi','audit_access_denied'=>'Amallar jurnaliga kirish rad etildi','backup_create'=>'Zaxira yaratildi'];
    public static function userData(?\app\models\User $user): ?array
    {
        if(!$user)return null;
        $data=array_intersect_key($user->attributes,array_flip(['id','username','name','role','category','org_id','active','hemis_id','degree','position','hemis_profile']));
        $data['hemis_profile']=json_decode($data['hemis_profile']??'{}',true)?:[];$data['password_change_required']=(bool)$user->must_change_password;
        return self::sanitize($data);
    }
    public static function sanitize($value)
    {
        if(!is_array($value))return $value;
        foreach($value as $key=>$item){
            if(preg_match('/password|parol|token|auth.?key|cookie|csrf|secret|credential|authorization|api.?key/i',(string)$key) && !is_bool($item))$value[$key]='[YASHIRILGAN]';
            else $value[$key]=self::sanitize($item);
        }
        return $value;
    }
    public static function encode(?int $actor,string $details): string
    {
        $payload=json_decode($details,true);if(!is_array($payload))$payload=['message'=>$details];
        $identity=$actor?(new Query())->select(['id','name','username','role'])->from('user')->where(['id'=>$actor])->one():null;
        $request=Yii::$app->request;$web=$request instanceof \yii\web\Request;
        $payload['_context']=['actor'=>$identity?:['id'=>$actor,'name'=>'Tizim'],'channel'=>$web?'web':'console','route'=>Yii::$app->controller?->getRoute(),'method'=>$web?$request->method:'CLI','ip'=>$web?$request->userIP:null];
        return json_encode(self::sanitize($payload),JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);
    }
    public static function label(string $action): string
    {
        if(isset(self::LABELS[$action]))return self::LABELS[$action];
        foreach(['application_superadmin_'=>'Superadministrator: ','application_'=>''] as $prefix=>$label)if(str_starts_with($action,$prefix))return $label.(Notifications::LABELS[substr($action,strlen($prefix))]??$action);
        return Notifications::LABELS[$action]??$action;
    }
    public static function format(string $details): string
    {
        $value=json_decode($details,true);
        return is_array($value)?json_encode(self::sanitize($value),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE):$details;
    }
}
