<?php
namespace app\services;
use app\models\User;
use yii\db\Query;
class Access
{
    public static function employeeIds(User $actor): ?array
    {
        if ($actor->isAdmin() || in_array($actor->role, ['expert','management'], true)) return null;
        if(in_array($actor->role,['head','faculty'],true) && $actor->org_id){$org=(new Query())->from('org')->where(['id'=>$actor->org_id])->one();if(!$org || empty($org['type_verified']) || $org['kind']!==($actor->role==='head'?'department':'faculty'))return [$actor->id];}
        if ($actor->role === 'head' && $actor->org_id) return (new Query())->select('id')->from('user')->where(['org_id'=>$actor->org_id])->column();
        if ($actor->role === 'faculty' && $actor->org_id) {
            $orgs = (new Query())->select('id')->from('org')->where(['or',['id'=>$actor->org_id],['parent_id'=>$actor->org_id]])->column();
            return (new Query())->select('id')->from('user')->where(['org_id'=>$orgs])->column();
        }
        return [$actor->id];
    }
    public static function canView(User $actor, array $application): bool
    {
        if ((int)$application['employee_id'] === (int)$actor->id) return true;
        if ($application['status'] === 'draft') return $actor->isAdmin();
        $ids = self::employeeIds($actor);
        return $ids === null || in_array($application['employee_id'], $ids);
    }
}
