<?php
namespace app\services;
use Yii;
use DomainException;
use yii\db\Query;
class Organization
{
    public static function save(string $name,string $kind,?int $parent,?int $id=null): int
    {
        $name=trim($name);$parent=$parent?:null;
        if($name==='' || mb_strlen($name)>200 || !in_array($kind,['university','faculty','department'],true))throw new DomainException('Nom va turini to‘g‘ri kiriting.');
        $old=$id?(new Query())->from('org')->where(['id'=>$id])->one():null;
        if($id && !$old)throw new DomainException('Bo‘lim topilmadi.');
        if($old && $old['kind']!==$kind)throw new DomainException('Mavjud bo‘lim turi o‘zgartirilmaydi.');
        $p=$parent?(new Query())->from('org')->where(['id'=>$parent])->one():null;
        if(($kind==='university' && $parent) || ($kind==='faculty' && (!$p || $p['kind']!=='university')) || ($kind==='department' && (!$p || $p['kind']!=='faculty')))throw new DomainException('Avval universitetni, keyin unga fakultetni, fakultetga kafedrani biriktiring.');
        $values=['name'=>$name,'kind'=>$kind,'parent_id'=>$parent];$cmd=Yii::$app->db->createCommand();
        if($id)$cmd->update('org',$values,['id'=>$id])->execute();else{$cmd->insert('org',$values)->execute();$id=(int)Yii::$app->db->lastInsertID;}
        return $id;
    }
    public static function labels(array $rows): array
    {
        $map=array_column($rows,null,'id');$labels=[];
        foreach($rows as $row){$parts=[$row['name']];$p=$row['parent_id'];$seen=[$row['id']=>true];while($p && isset($map[$p]) && !isset($seen[$p])){$seen[$p]=true;array_unshift($parts,$map[$p]['name']);$p=$map[$p]['parent_id'];}$labels[$row['id']]=implode(' → ',$parts);}
        asort($labels);return $labels;
    }
}
