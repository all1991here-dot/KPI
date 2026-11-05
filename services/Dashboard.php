<?php
namespace app\services;

use DateTimeImmutable;
use yii\db\Query;

/** Dashboard aggregates always receive the controller's authorized query. */
class Dashboard
{
    public static function details(Query $scope, ?array $period): array
    {
        $start=new DateTimeImmutable($period ? substr($period['starts'],0,7).'-01' : date('Y').'-01-01');
        $end=new DateTimeImmutable($period ? substr($period['ends'],0,7).'-01' : date('Y').'-12-01');
        // Keep long periods readable without hiding the date range from the user.
        if($start<$end->modify('-23 months'))$start=$end->modify('-23 months');
        $names=['Yan','Fev','Mar','Apr','May','Iyn','Iyl','Avg','Sen','Okt','Noy','Dek'];$series=[];
        for($date=$start;$date<=$end;$date=$date->modify('+1 month')){
            $key=$date->format('Y-m');$series[$key]=['key'=>$key,'label'=>$names[(int)$date->format('n')-1].' '.$date->format('Y'),'cents'=>0];
        }
        $approved=(clone $scope)->andWhere(['a.status'=>'approved']);
        $activities=(clone $approved)->select(['a.activity_date','a.score_cents'])->andWhere(['>=','a.activity_date',$start->format('Y-m-d')])->andWhere(['<','a.activity_date',$end->modify('+1 month')->format('Y-m-d')])->all();
        foreach($activities as $row){$key=substr($row['activity_date'],0,7);if(isset($series[$key]))$series[$key]['cents']+=(int)$row['score_cents'];}
        $awaiting=(clone $scope)->select(['a.*','employee'=>'u.name','indicator'=>'i.name','period'=>'p.name'])->andWhere(['a.status'=>['submitted','review']])->orderBy(['a.created_at'=>SORT_ASC])->limit(5)->all();
        $leaders=(clone $approved)->select(['name'=>'o.name','score_cents'=>'SUM(a.score_cents)','applications'=>'COUNT(*)'])->leftJoin(['o'=>'org'],'o.id=u.org_id')->groupBy(['o.id','o.name'])->orderBy(['score_cents'=>SORT_DESC])->limit(5)->all();
        return ['series'=>array_values($series),'chartStart'=>$start->format('m.Y'),'chartEnd'=>$end->format('m.Y'),'awaiting'=>$awaiting,'leaders'=>$leaders];
    }
}
