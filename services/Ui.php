<?php
namespace app\services;
use yii\helpers\Html;
class Ui
{
    public static function h($value): string {return Html::encode((string)$value);}
    public static function money($cents): string {return number_format((int)$cents/100,2,'.',' ');}
    public static function input(string $label,string $name,$value='',string $type='text',array $options=[]): string
    {
        return '<label class="field"><span>'.self::h($label).'</span>'.Html::input($type,$name,$value,$options).'</label>';
    }
    public static function select(string $label,string $name,$value,array $items,array $options=[]): string
    {
        return '<label class="field"><span>'.self::h($label).'</span>'.Html::dropDownList($name,$value,$items,$options).'</label>';
    }
    public static function area(string $label,string $name,$value='',array $options=[]): string
    {
        return '<label class="field"><span>'.self::h($label).'</span>'.Html::textarea($name,$value,$options+['rows'=>3]).'</label>';
    }
    public static function status(string $status): string {return '<span class="badge '.self::h($status).'">'.self::h(Workflow::STATUSES[$status]??$status).'</span>';}
    public static function empty(string $text): string {return '<div class="empty"><b>Hozircha ma’lumot yo‘q</b><p>'.self::h($text).'</p></div>';}
}
