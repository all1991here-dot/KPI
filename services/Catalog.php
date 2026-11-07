<?php
namespace app\services;
use Yii;
use yii\db\Query;
class Catalog
{
    public const CATEGORIES = ['DSC'=>'DSc professor / dotsent', 'PHD'=>'PhD / fan nomzodi', 'DARAJASIZ'=>'Ilmiy darajasiz'];
    public static function import(): int
    {
        $xml = simplexml_load_file(Yii::getAlias('@app/data/criteria.xml'), 'SimpleXMLElement', LIBXML_NONET);
        if (!$xml) throw new \RuntimeException('Mezonlar katalogi o‘qilmadi.');
        $directions=[]; $criteria=[];
        foreach ($xml->Yonalish as $row) $directions[(string)$row['Kod']]=$row;
        foreach ($xml->Mezon as $row) $criteria[(string)$row['Kod']]=$row;
        $count=0;
        foreach ($xml->Korsatkich as $row) {
            $code=(string)$row['Kod'];
            if ((new Query())->from('indicator')->where(['code'=>$code])->exists()) continue;
            $criterion=$criteria[(string)$row['Mezon']]; $direction=$directions[(string)$criterion['Yonalish']];
            Yii::$app->db->createCommand()->insert('indicator',[
                'code'=>$code,'category'=>(string)$direction['Toifa'],'section'=>(string)$direction['Kod'],'section_name'=>(string)$direction['Nom'],
                'criterion_code'=>(string)$criterion['Kod'],'criterion_name'=>(string)$criterion['Tavsif'],'document_number'=>(string)$criterion['HujjatRaqami'],
                'name'=>(string)$row['Nom'],'source_text'=>(string)$row['Matn'],'source_note'=>(string)$row['Izoh'],'source_page'=>(int)$row['Sahifa'],
                'calc_type'=>(string)$row['Hisoblash'],'base_score'=>(string)$row['Ball']!==''?(string)$row['Ball']:null,
                'upper_score'=>(string)$row['Yuqori']!==''?(string)$row['Yuqori']:null,'cap_score'=>(string)$row['Maksimal']!==''?(string)$row['Maksimal']:null,
                'once_only'=>(string)$row['BirMartalik']==='true'?1:0,'policy_required'=>(string)$row['Tasdiq']==='true'?1:0,'bonus'=>(string)$row['Ragbat']==='true'?1:0,
            ])->execute(); $count++;
        }
        return $count;
    }
    public static function createRules(int $periodId): void
    {
        foreach ((new Query())->from('indicator')->where(['deleted_at'=>null])->all() as $row) {
            if ((new Query())->from('period_rule')->where(['period_id'=>$periodId,'indicator_id'=>$row['id']])->exists()) continue;
            $values=array_intersect_key($row,array_flip(['calc_type','base_score','upper_score','cap_score','once_only']));
            Yii::$app->db->createCommand()->insert('period_rule',$values+['period_id'=>$periodId,'indicator_id'=>$row['id'],'enabled'=>$row['policy_required']?0:1,'decision'=>$row['policy_required']?'':'Manba hujjatidagi aniq qoida','revision'=>1])->execute();
        }
    }
    public static function audit(?int $actor, string $action, string $details): void
    {
        Yii::$app->db->createCommand()->insert('audit',['actor_id'=>$actor,'action'=>$action,'details'=>Audit::encode($actor,$details),'created_at'=>time()])->execute();
    }
}
