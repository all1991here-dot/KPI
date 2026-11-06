<?php
namespace app\services;
use DomainException;
class HemisPayload
{
    public static function page(array $json): array
    {
        if(isset($json['success']) && !$json['success'])throw new DomainException('HEMIS so‘rovni rad etdi. API kaliti huquqlarini tekshiring.');
        $items=$json['data']['items']??$json['items']??$json['data']['employees']??$json['employees']??null;
        if($items===null && isset($json['data']) && is_array($json['data']) && array_is_list($json['data']))$items=$json['data'];
        if(!is_array($items) || !array_is_list($items))throw new DomainException('HEMIS javobida xodimlar ro‘yxati topilmadi. data.items yoki items formati kutiladi.');
        $pagination=$json['data']['pagination']??$json['pagination']??$json['data']['_meta']??$json['_meta']??[];
        $count=$pagination['pageCount']??$pagination['page_count']??null;
        if($count===null && isset($pagination['totalCount'],$pagination['perPage']) && (int)$pagination['perPage']>0)$count=(int)ceil((int)$pagination['totalCount']/(int)$pagination['perPage']);
        return [$items,$count===null?null:max(0,(int)$count)];
    }
    public static function employee($item): ?array
    {
        if(!is_array($item))return null;
        $nested=$item['employee']??$item['_employee']??null;
        if(is_array($nested)){$id=$nested['id']??$item['id']??null;$item=array_replace($nested,$item);$item['id']=$id;}
        $item['id']=$item['id']??$item['employee_id']??$item['_id']??null;
        $name='';foreach(['full_name','fullName','full_name_latin','name'] as $key){if(isset($item[$key]) && is_scalar($item[$key]) && trim((string)$item[$key])!==''){$name=trim((string)$item[$key]);break;}}
        if($name===''){$parts=[];foreach([['second_name','last_name','surname'],['first_name','given_name'],['third_name','middle_name','patronymic']] as $aliases){foreach($aliases as $key)if(isset($item[$key]) && is_scalar($item[$key])){$parts[]=trim((string)$item[$key]);break;}}$name=trim(implode(' ',$parts));}
        $item['full_name']=$name;return $item;
    }
}
