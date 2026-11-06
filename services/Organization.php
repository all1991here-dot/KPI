<?php
namespace app\services;
use Yii;
use DomainException;
use yii\db\Query;
class Organization
{
    public const KINDS=['university'=>'Universitet','faculty'=>'Fakultet','department'=>'Kafedra','unit'=>'Bo‘lim'];
    public static function allowedParent(string $kind,?string $parentKind): bool
    {
        return $kind==='university'?$parentKind===null:($kind==='faculty'?$parentKind==='university':(in_array($kind,['department','unit'],true) && in_array($parentKind,['university','faculty'],true)));
    }
    public static function lineage(?int $id,array $rows): array
    {
        $map=array_column($rows,null,'id');$chain=[];$seen=[];
        while($id && isset($map[$id]) && !isset($seen[$id])){$seen[$id]=true;$row=$map[$id];$key=!empty($row['type_verified']??1)?$row['kind']:'unit';$chain[$key]??=$row;$id=$row['parent_id']?(int)$row['parent_id']:null;}
        return $chain;
    }

    public static function save(string $name,string $kind,?int $parent,?int $id=null): int
    {
        $name=trim($name);$parent=$parent?:null;
        if($name==='' || mb_strlen($name)>200 || !isset(self::KINDS[$kind]))throw new DomainException('Nom va turini to‘g‘ri kiriting.');
        $old=$id?(new Query())->from('org')->where(['id'=>$id])->one():null;
        if($id && !$old)throw new DomainException('Bo‘lim topilmadi.');
        if($old && $old['kind']==='university' && $kind!=='university')throw new DomainException('Universitet turini almashtirib bo‘lmaydi.');
        if($id && $parent===$id)throw new DomainException('Bo‘lim o‘ziga biriktirilmaydi.');
        if($old && $old['kind']!==$kind){foreach((new Query())->from('org')->where(['parent_id'=>$id])->all() as $child)if(!self::allowedParent($child['kind'],$kind))throw new DomainException('Avval quyi tuzilmalarni mos yuqori bo‘limga ko‘chiring.');}
        $p=$parent?(new Query())->from('org')->where(['id'=>$parent])->one():null;
        if(!self::allowedParent($kind,$p?$p['kind']:null))throw new DomainException('Fakultet universitetga, kafedra va bo‘lim universitet yoki fakultetga biriktiriladi.');
        if($p && $p['kind']==='faculty' && empty($p['type_verified']))throw new DomainException('Avval yuqori fakultetning turini tasdiqlang.');
        $values=['name'=>$name,'kind'=>$kind,'parent_id'=>$parent];$cmd=Yii::$app->db->createCommand();
        if($id)$cmd->update('org',$values,['id'=>$id])->execute();else{$cmd->insert('org',$values)->execute();$id=(int)Yii::$app->db->lastInsertID;}
        return $id;
    }
    public static function blockedBy(?int $id,?array $rows=null): ?array
    {
        if(!$id)return null;
        $map=array_column($rows??(new Query())->from('org')->all(),null,'id');$seen=[];
        while($id && isset($map[$id]) && !isset($seen[$id])){$seen[$id]=true;$row=$map[$id];if(!empty($row['blocked_at']))return $row;$id=$row['parent_id']?(int)$row['parent_id']:null;}
        return null;
    }
    public static function setBlocked(\app\models\User $actor,int $id,bool $blocked): void
    {
        Management::authorize($actor);
        Yii::$app->db->transaction(function()use($actor,$id,$blocked){
            $old=(new Query())->from('org')->where(['id'=>$id])->one();if(!$old)throw new DomainException('Bo‘lim topilmadi.');
            Yii::$app->db->createCommand()->update('org',['blocked_at'=>$blocked?($old['blocked_at']?:time()):null],['id'=>$id])->execute();
            Catalog::audit((int)$actor->id,$blocked?'org_block':'org_unblock',json_encode(['id'=>$id,'name'=>$old['name'],'previous'=>$old['blocked_at']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));
        });
    }
    public static function saveDetails(\app\models\User $actor,array $data,?int $id,string $kind): int
    {
        if(!$actor->isAdmin())throw new \yii\web\ForbiddenHttpException('Administrator huquqi kerak.');
        if(!$id)Management::authorize($actor);
        return Yii::$app->db->transaction(function()use($actor,$data,$id,$kind){
            $old=$id?(new Query())->from('org')->where(['id'=>$id])->one():null;
            if($id && !$old)throw new DomainException('Bo‘lim topilmadi.');
            if($old && $old['kind']!==$kind && $actor->role!=='superadmin')throw new \yii\web\ForbiddenHttpException('Tuzilma turini faqat superadministrator o‘zgartiradi.');
            $parent=(int)($data['parent_id']??0);
            if($old && $actor->role!=='superadmin' && (int)$old['parent_id']!==$parent && self::blockedBy((int)$old['id']))throw new \yii\web\ForbiddenHttpException('Bloklangan bo‘limni boshqa tuzilmaga faqat superadministrator ko‘chiradi.');
            if($parent && (!$old || (int)$old['parent_id']!==$parent) && self::blockedBy($parent))throw new DomainException('Bloklangan universitet yoki fakultetga yangi bo‘lim biriktirilmaydi.');
            $hemis=trim((string)($data['hemis_id']??$old['hemis_id']??''));
            if($hemis!=='' && !preg_match('/^[0-9]{1,40}$/',$hemis))throw new DomainException('HEMIS ID faqat raqamlardan iborat bo‘lsin.');
            if($actor->role!=='superadmin' && $hemis!==(string)($old['hemis_id']??''))throw new \yii\web\ForbiddenHttpException('HEMIS bog‘lanishini faqat superadministrator o‘zgartiradi.');
            if($hemis!=='' && (new Query())->from('org')->where(['hemis_id'=>$hemis])->andFilterWhere($id?['<>','id',$id]:[])->exists())throw new DomainException('HEMIS ID boshqa bo‘limda mavjud.');
            $values=['hemis_id'=>$hemis?:null];
            if($actor->role==='superadmin'){$values['type_verified']=1;$values['needs_review']=0;$values['classification_note']=null;$values['manual_structure']=!empty($data['manual_structure']) || ($old && ($old['kind']!==$kind || (int)$old['parent_id']!==$parent))?1:0;}
            foreach(['short_name'=>120,'code'=>80,'head_name'=>200,'phone'=>80,'email'=>200,'address'=>500,'notes'=>10000] as $key=>$max){$value=trim((string)($data[$key]??$old[$key]??''));if(mb_strlen($value)>$max)throw new DomainException('Rekvizit uzunligi ruxsat etilgan chegaradan oshdi.');$values[$key]=$value===''?null:$value;}
            if($values['email'] && !filter_var($values['email'],FILTER_VALIDATE_EMAIL))throw new DomainException('Email manzilini to‘g‘ri kiriting.');
            $saved=self::save((string)($data['name']??''),$kind,$parent?:null,$id);
            Yii::$app->db->createCommand()->update('org',$values,['id'=>$saved])->execute();
            Catalog::audit((int)$actor->id,'org_details_save',json_encode(['id'=>$saved,'before'=>$old,'after'=>$values+['name'=>trim((string)$data['name']),'kind'=>$kind,'parent_id'=>$parent?:null]],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR));return $saved;
        });
    }
    public static function labels(array $rows): array
    {
        $map=array_column($rows,null,'id');$labels=[];
        foreach($rows as $row){$parts=[$row['name']];$p=$row['parent_id'];$seen=[$row['id']=>true];while($p && isset($map[$p]) && !isset($seen[$p])){$seen[$p]=true;array_unshift($parts,$map[$p]['name']);$p=$map[$p]['parent_id'];}$labels[$row['id']]=implode(' → ',$parts);}
        asort($labels);return $labels;
    }
}
