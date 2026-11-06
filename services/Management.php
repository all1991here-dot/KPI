<?php
namespace app\services;
use Yii;
use DomainException;
use yii\db\Query;
use app\models\User;
class Management
{
    public static function authorize(User $actor): void
    {
        if($actor->role!=='superadmin')throw new \yii\web\ForbiddenHttpException('Bu amal faqat superadministratorga ruxsat etilgan.');
    }
    public static function deleteOrganization(User $actor,int $id): void
    {
        self::authorize($actor);
        Yii::$app->db->transaction(function()use($actor,$id){
            $row=(new Query())->from('org')->where(['id'=>$id])->one();if(!$row)throw new DomainException('Bo‘lim topilmadi.');
            if((new Query())->from('org')->where(['parent_id'=>$id])->exists())throw new DomainException('Avval quyi fakultet/kafedralarni boshqa bo‘limga ko‘chiring yoki o‘chiring.');
            if(User::find()->where(['org_id'=>$id])->exists())throw new DomainException('Avval biriktirilgan xodimlarni boshqa bo‘limga ko‘chiring.');
            Catalog::audit((int)$actor->id,'org_delete',json_encode($row,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
            Yii::$app->db->createCommand()->delete('org',['id'=>$id])->execute();
        });
    }
    public static function deleteUser(User $actor,int $id): void
    {
        self::authorize($actor);
        if($id===(int)$actor->id)throw new DomainException('O‘z hisobingizni o‘chira olmaysiz.');
        Yii::$app->db->transaction(function()use($actor,$id){
            $user=User::findOne($id);if(!$user || $user->deleted_at)throw new DomainException('Xodim topilmadi.');
            Catalog::audit((int)$actor->id,'user_delete',json_encode(['id'=>$id,'name'=>$user->name,'role'=>$user->role,'org_id'=>$user->org_id,'hemis_id'=>$user->hemis_id],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
            $user->active=0;$user->deleted_at=time();$user->org_id=null;$user->auth_key=Yii::$app->security->generateRandomString();$user->save(false);
        });
    }
    public static function setUserActive(User $actor,int $id,bool $active): void
    {
        if(!$actor->isAdmin())throw new \yii\web\ForbiddenHttpException('Administrator huquqi kerak.');
        Yii::$app->db->transaction(function()use($actor,$id,$active){
            $target=User::findOne(['id'=>$id,'deleted_at'=>null]);if(!$target)throw new DomainException('Hisob topilmadi.');
            if($actor->role!=='superadmin' && $target->isAdmin())throw new \yii\web\ForbiddenHttpException('Administrator hisobini faqat superadministrator boshqaradi.');
            if(!$active && $id===(int)$actor->id)throw new DomainException('O‘z hisobingizni bloklay olmaysiz.');
            $target->active=$active?1:0;$target->auth_key=Yii::$app->security->generateRandomString();$target->save(false);
            Catalog::audit((int)$actor->id,$active?'user_unblock':'user_block','User #'.$id);
        });
    }
    public static function bulkPreview(User $actor,string $scope): array
    {
        self::authorize($actor);$assignments=[];
        if($scope==='users')$records=(new Query())->select(['id','name','username','role'])->from('user')->where(['deleted_at'=>null])->andWhere(['<>','id',$actor->id])->orderBy('id')->all();
        elseif($scope==='structure'){$records=(new Query())->from('org')->orderBy('id')->all();$assignments=(new Query())->select(['id','org_id'])->from('user')->where(['org_id'=>array_column($records,'id')])->orderBy('id')->all();}
        else throw new DomainException('Ro‘yxat turi noto‘g‘ri.');
        $fingerprint=hash_hmac('sha256',json_encode([$scope,(int)$actor->id,$records,$assignments],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),Yii::$app->params['cookieKey']);
        return ['count'=>count($records),'records'=>$records,'assignments'=>$assignments,'affected_users'=>count($assignments),'fingerprint'=>$fingerprint];
    }
    public static function deleteAll(User $actor,string $scope,string $fingerprint): int
    {
        self::authorize($actor);
        return Yii::$app->db->transaction(function()use($actor,$scope,$fingerprint){
            $preview=self::bulkPreview($actor,$scope);
            if(!hash_equals($preview['fingerprint'],$fingerprint))throw new DomainException('Ro‘yxat o‘zgargan. Yangi sonlarni tekshirib, qayta tasdiqlang.');
            $ids=array_column($preview['records'],'id');if(!$ids)return 0;
            Catalog::audit((int)$actor->id,'bulk_delete_'.$scope,json_encode(['records'=>$preview['records'],'assignments'=>$preview['assignments']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
            if($scope==='users'){foreach($ids as $id)self::deleteUser($actor,(int)$id);}
            else {
                Yii::$app->db->createCommand()->update('user',['org_id'=>null],['org_id'=>$ids])->execute();
                $remaining=count($ids);
                while($remaining){
                    $parents=(new Query())->select('parent_id')->from('org')->where(['not',['parent_id'=>null]]);
                    $leaves=(new Query())->select('id')->from('org')->where(['id'=>$ids])->andWhere(['not in','id',$parents])->column();
                    if(!$leaves)throw new DomainException('Tuzilma bog‘lanishi o‘zgargan yoki aylanma bog‘lanish bor. Amal bekor qilindi.');
                    Yii::$app->db->createCommand()->delete('org',['id'=>$leaves])->execute();$remaining-=count($leaves);
                }
            }
            return count($ids);
        });
    }
    public static function saveIndicator(User $actor,array $data,?int $id=null): int
    {
        self::authorize($actor);
        return Yii::$app->db->transaction(function()use($actor,$data,$id){
            $old=$id?(new Query())->from('indicator')->where(['id'=>$id,'deleted_at'=>null])->one():null;
            if($id && !$old)throw new DomainException('Mezon topilmadi.');
            $values=[];
            foreach(['code'=>120,'name'=>1000,'section'=>40,'section_name'=>250,'criterion_code'=>120,'criterion_name'=>1000] as $key=>$max){$value=trim((string)($data[$key]??''));if($value==='' || mb_strlen($value)>$max)throw new DomainException('Mezon nomi, kodi, yo‘nalishi va bandini to‘liq kiriting.');$values[$key]=$value;}
            if(!preg_match('/^[A-Za-z0-9_.-]+$/',$values['code']))throw new DomainException('Mezon kodi lotin harfi, raqam, tire, nuqta va pastki chiziqdan iborat bo‘lsin.');
            $values['category']=(string)($data['category']??'');if(!isset(Catalog::CATEGORIES[$values['category']]))throw new DomainException('Toifa noto‘g‘ri.');
            if($old && $values['category']!==$old['category'] && (new Query())->from('application')->where(['indicator_id'=>$id])->exists())throw new DomainException('Hujjatlarda ishlatilgan mezon toifasini o‘zgartirmang; yangi mezon yarating.');
            if((new Query())->from('indicator')->where(['code'=>$values['code']])->andFilterWhere($id?['<>','id',$id]:[])->exists())throw new DomainException('Bu mezon kodi mavjud yoki o‘chirilgan katalogda saqlangan. Boshqa kod tanlang.');
            $values['calc_type']=(string)($data['calc_type']??'');if(!in_array($values['calc_type'],['QatiyBall','BirlikUchun','ChegaraliYigindi','Ballgacha','DarajaShkalasi','PulFormulasi','XirshIndeksi'],true))throw new DomainException('Hisoblash turi noto‘g‘ri.');
            foreach(['base_score','upper_score','cap_score'] as $key){$value=trim((string)($data[$key]??''));if($value!=='' && (!preg_match('/^\d{1,7}(\.\d{1,2})?$/',$value)))throw new DomainException('Ball 0–9999999.99 oralig‘ida bo‘lsin.');$values[$key]=$value===''?null:$value;}
            foreach(['once_only','policy_required','bonus'] as $key)$values[$key]=empty($data[$key])?0:1;
            if(in_array($values['calc_type'],['QatiyBall','XirshIndeksi'],true))$values['once_only']=1;
            if(!$values['policy_required'] && $values['calc_type']==='ChegaraliYigindi' && $values['cap_score']===null)throw new DomainException('Limitli mezon uchun maksimal jami ball kerak.');
            if(in_array($values['calc_type'],['PulFormulasi','XirshIndeksi'],true))$values['policy_required']=1;
            if(!$values['policy_required']){$required=in_array($values['calc_type'],['Ballgacha','DarajaShkalasi'],true)?'upper_score':'base_score';if($values[$required]===null)throw new DomainException('Ochiq mezon uchun birlik balli yoki ekspert chegarasini kiriting.');}
            foreach(['source_text','source_note'] as $key){$value=trim((string)($data[$key]??''));if(mb_strlen($value)>20000)throw new DomainException('Mezon izohi juda uzun.');$values[$key]=$value;}
            $values['source_page']=max(0,min(100000,(int)($data['source_page']??0)));$values['document_number']=mb_substr(trim((string)($data['document_number']??'')),0,10);
            if($id)Yii::$app->db->createCommand()->update('indicator',$values,['id'=>$id])->execute();
            else{Yii::$app->db->createCommand()->insert('indicator',$values)->execute();$id=(int)Yii::$app->db->lastInsertID;}
            foreach((new Query())->from('period')->select('id')->where(['closed'=>0])->column() as $periodId)Catalog::createRules((int)$periodId);
            Catalog::audit((int)$actor->id,'indicator_save',json_encode(['id'=>$id,'before'=>$old,'after'=>$values],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));return $id;
        });
    }
    public static function deleteIndicator(User $actor,int $id): void
    {
        self::authorize($actor);
        Yii::$app->db->transaction(function()use($actor,$id){
            $old=(new Query())->from('indicator')->where(['id'=>$id,'deleted_at'=>null])->one();if(!$old)throw new DomainException('Mezon topilmadi.');
            Yii::$app->db->createCommand()->update('indicator',['deleted_at'=>time()],['id'=>$id])->execute();
            Catalog::audit((int)$actor->id,'indicator_delete',json_encode($old,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        });
    }
}
