<?php
namespace app\services;
use app\models\User;
use yii\db\Query;
class Access
{
    public static function isGlobal(User $actor): bool
    {
        return in_array($actor->role,['admin','superadmin','management'],true);
    }
    public static function employeeIds(User $actor): ?array
    {
        if(self::isGlobal($actor))return null;
        $own=[(int)$actor->id];
        if(!in_array($actor->role,['head','faculty'],true) || !$actor->org_id)return $own;
        $root=(new Query())->from('org')->where(['id'=>$actor->org_id])->one();
        if(!$root || empty($root['type_verified']) || $root['kind']!==($actor->role==='head'?'department':'faculty'))return $own;
        $children=[];
        foreach((new Query())->select(['id','parent_id'])->from('org')->all() as $org)$children[(int)$org['parent_id']][]=(int)$org['id'];
        $seen=[];$pending=[(int)$actor->org_id];
        while($pending){$id=array_pop($pending);if(isset($seen[$id]))continue;$seen[$id]=true;foreach($children[$id]??[] as $child)$pending[]=$child;}
        $employees=(new Query())->select('id')->from('user')->where(['org_id'=>array_keys($seen)])->column();
        return array_values(array_unique(array_merge($own,array_map('intval',$employees))));
    }
    /** Operational review access is separate from the expert's KPI totals. */
    public static function reviewEmployeeIds(User $actor): ?array
    {
        return $actor->role==='expert'?null:self::employeeIds($actor);
    }
    public static function scope(Query $query,User $actor,bool $reviewQueue=false): Query
    {
        if(self::isGlobal($actor))return $query;
        if($actor->role==='expert' && !$reviewQueue){
            $approved=(new Query())->from(['permission_review'=>'review'])->where('permission_review.application_id=a.id')->andWhere(['permission_review.actor_id'=>$actor->id,'permission_review.action'=>'approve']);
            return $query->andWhere(['or',['a.employee_id'=>$actor->id],['exists',$approved]]);
        }
        $ids=$reviewQueue?self::reviewEmployeeIds($actor):self::employeeIds($actor);
        return $ids===null?$query:$query->andWhere(['a.employee_id'=>$ids]);
    }
    public static function canView(User $actor,array $application): bool
    {
        if((int)$application['employee_id']===(int)$actor->id || self::isGlobal($actor))return true;
        if($actor->role==='expert'){
            if((new Query())->from('review')->where(['application_id'=>$application['id'],'actor_id'=>$actor->id,'action'=>'approve'])->exists())return true;
            if(!in_array($application['status'],['submitted','review'],true))return false;
            $steps=json_decode($application['route_snapshot']??'[]',true);$step=$steps[(int)$application['step']]??null;
            return $step && in_array('expert',json_decode($step['roles'],true),true) && (new Query())->from('period')->where(['id'=>$application['period_id'],'closed'=>0])->exists();
        }
        $ids=self::employeeIds($actor);
        return in_array((int)$application['employee_id'],$ids??[],true);
    }
}
