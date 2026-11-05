<?php
namespace app\services;
use yii\helpers\Html;
class Ui
{
    public static function icon(string $name, string $class=''): string
    {
        $paths=[
            'campus'=>'<path d="m2 8 10-5 10 5H2M3 21h18M5 18V10M10 18V10M14 18V10M19 18V10M3 18h18"/>',
            'grid'=>'<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>',
            'file'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
            'plus'=>'<path d="M12 5v14M5 12h14"/>',
            'book'=>'<path d="M12 6c-3-3-7-3-10-2v15c4-1 7-1 10 2 3-3 6-3 10-2V4c-3-1-7-1-10 2zM12 6v15"/>',
            'chart'=>'<path d="M3 3v18h18M7 14l4-4 4 3 6-8"/>',
            'bell'=>'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
            'user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
            'users'=>'<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M22 21v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
            'check'=>'<path d="M9 12l2 2 4-4M12 3l8 4v5c0 5-8 9-8 9s-8-4-8-9V7z"/>',
            'building'=>'<path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-4h6v4M9 9h1M14 9h1M9 13h1M14 13h1"/>',
            'calendar'=>'<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18M8 15h2M14 15h2"/>',
            'settings'=>'<path d="M4 7h16M4 17h16"/><circle cx="8" cy="7" r="3"/><circle cx="16" cy="17" r="3"/>',
            'route'=>'<circle cx="6" cy="5" r="2"/><circle cx="18" cy="19" r="2"/><path d="M6 7v8a4 4 0 0 0 4 4h6M6 11h8a4 4 0 0 0 4-4V3"/>',
            'link'=>'<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-2 2M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l2-2"/>',
            'history'=>'<path d="M3 11a9 9 0 1 1 2 7M3 4v7h7M12 7v5l3 2"/>',
            'logout'=>'<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
            'search'=>'<circle cx="10.5" cy="10.5" r="7.5"/><path d="M16 16l5 5"/>',
            'moon'=>'<path d="M21 13A9 9 0 0 1 11 3 9 9 0 1 0 21 13z"/>',
            'sun'=>'<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/>',
            'menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>',
            'close'=>'<path d="M6 6l12 12M18 6 6 18"/>',
            'arrow'=>'<path d="M5 12h14M13 6l6 6-6 6"/>',
            'clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
            'return'=>'<path d="M9 4 3 10l6 6M3 10h11a7 7 0 0 1 7 7v3"/>',
            'spark'=>'<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z"/>',
            'target'=>'<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
            'folder'=>'<path d="M3 7V5a2 2 0 0 1 2-2h5l2 3h7a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/>',
        ];
        return '<svg class="ui-icon '.self::h($class).'" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'.($paths[$name]??$paths['grid']).'</svg>';
    }
    public static function h($value): string {return Html::encode((string)$value);}
    public static function money($cents): string {return number_format((int)$cents/100,2,'.',' ');}
    public static function brandLogo(string $class=''): string
    {
        $path=\Yii::getAlias('@app/web/images/fardu-logo.png');
        if(is_file($path))return Html::img(\Yii::$app->request->baseUrl.'/images/fardu-logo.png?v=3',['class'=>'fardu-logo '.$class,'alt'=>'Farg‘ona davlat universiteti logotipi']);
        return '<span class="brand-fallback '.self::h($class).'">'.self::icon('campus').'<span>FarDU<small>KPI BAHOLASH TIZIMI</small></span></span>';
    }
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
    public static function empty(string $text): string {return '<div class="empty"><span class="empty-icon">'.self::icon('folder').'</span><b>Hozircha ma’lumot yo‘q</b><p>'.self::h($text).'</p></div>';}
}
