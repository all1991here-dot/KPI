<?php
namespace app\services;
use Yii;
use yii\db\Query;
class OrganizationClassifier
{
    public static function wordKind($text,bool $type=false): ?string
    {
        if(!is_scalar($text))return null;$s=mb_strtolower(trim((string)$text));
        $s=str_replace(['‘','’','ʻ','ʼ','`','ʹ'],"'",$s);
        if(preg_match('/kafedra|кафедр|қaфедр|chair|academic department/u',$s))return 'department';
        if(preg_match('/bo.?lim|бўлим|булим|отдел|markaz|марказ|центр|center|centre|boshqarma|бошқарма|dekanat|деканат|office|administration|служб|laborator|лаборатор|rektorat|ректорат|kutubxona|библиотек/u',$s))return 'unit';
        if(preg_match('/fakult|факульт|факултет|faculty/u',$s))return 'faculty';
        if(preg_match('/universitet|university|университет|^otm$/u',$s))return 'university';
        if($type && preg_match('/^departments?$/u',$s))return 'department';
        return null;
    }
    public static function type(array $item): array
    {
        $raw=$item['structureType']??$item['structure_type']??[];
        $code=is_array($raw)?($raw['code']??$raw['id']??''):$raw;
        $name=is_array($raw)?($raw['name']??$raw['title']??''):(!is_numeric($raw)?$raw:'');
        return [is_scalar($code)?mb_substr((string)$code,0,80):'',is_scalar($name)?mb_substr((string)$name,0,200):''];
    }
    /** Learn code meanings from explicit labels in this payload; codes are not universal. */
    public static function classify(array $items): array
    {
        $evidence=[];$prepared=[];$ambiguous=[];
        foreach($items as $id=>$item){
            [$code,$typeName]=self::type($item);$type=self::wordKind($typeName,true);$named=self::wordKind($item['name']??'');if($named==='university' && $type && $type!=='university')$named=null;
            $conflict=$type && $named && $type!==$named;if($conflict)$ambiguous[$code]=true;
            $kind=$conflict?(in_array($type,['department','unit'],true) && $named==='faculty'?$type:$named):($type??$named);
            if($kind && $code!=='' && !$conflict)$evidence[$code][$kind]=true;
            $prepared[$id]=['kind'=>$kind,'verified'=>$kind?1:0,'type_code'=>$code,'type_name'=>$typeName,'review'=>$conflict?1:0,'note'=>$conflict?'HEMIS turi va nomi mos emas; turini tekshiring.':''];
        }
        foreach($prepared as &$row){
            if(!$row['kind']){$meanings=array_keys($evidence[$row['type_code']]??[]);if(count($meanings)===1 && empty($ambiguous[$row['type_code']])){$row['kind']=$meanings[0];$row['verified']=1;}}
            if(!$row['kind']){$row['kind']='unit';$row['review']=1;$row['note']='HEMIS tur nomi aniqlanmadi. Superadministrator turini tanlasin.';}
        }unset($row);return $prepared;
    }
    /** Old imports have no source type labels. Repair only explicit names; flag the rest. */
    public static function repairLegacy(): void
    {
        $rows=(new Query())->from('org')->all();$byId=array_column($rows,null,'id');$childrenByParent=[];foreach($rows as $r)$childrenByParent[$r['parent_id']??0][]=$r;$changed=[];
        foreach($rows as $r){if(!$r['hemis_id'] || $r['kind']==='university')continue;
            $named=self::wordKind($r['name']);$values=['type_verified'=>0,'needs_review'=>1,'classification_note'=>'Oldingi HEMIS turi tekshirilmagan. Tuzilmani qayta import qiling yoki turini qo‘lda tasdiqlang.'];
            if($named && $named!=='university'){
                $children=$childrenByParent[$r['id']]??[];$safe=true;
                foreach($children as $child)if(!Organization::allowedParent($child['kind'],$named))$safe=false;
                $parent=$byId[$r['parent_id']]??null;
                if($safe && $parent && Organization::allowedParent($named,$parent['kind'])){$values['kind']=$named;$values['type_verified']=1;}
            }
            Yii::$app->db->createCommand()->update('org',$values,['id'=>$r['id']])->execute();$changed[]=['id'=>$r['id'],'old_kind'=>$r['kind'],'new_kind'=>$values['kind']??$r['kind']];
        }
        if($changed)Catalog::audit(null,'org_classification_legacy_review',json_encode($changed,JSON_THROW_ON_ERROR));
    }
}
