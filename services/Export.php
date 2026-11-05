<?php
namespace app\services;
use Yii;
class Export
{
    public static function download(array $rows,string $format)
    {
        if($format==='csv'){
            $stream=fopen('php://temp','r+');fwrite($stream,"\xEF\xBB\xBF");foreach($rows as $row)fputcsv($stream,array_map(fn($s)=>preg_match('/^[=+@\-\t\r]/',(string)$s)?"'".$s:$s,$row));rewind($stream);$data=stream_get_contents($stream);fclose($stream);
            return Yii::$app->response->sendContentAsFile($data,'kpi-hisobot.csv',['mimeType'=>'text/csv; charset=UTF-8']);
        }
        $escape=fn($s)=>htmlspecialchars(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u','',(string)$s),ENT_XML1|ENT_QUOTES,'UTF-8');
        $sheet='<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach($rows as $idx=>$row){$sheet.='<row r="'.($idx+1).'">';foreach($row as $col=>$value){$ref=chr(65+$col).($idx+1);$sheet.='<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.$escape($value).'</t></is></c>';}$sheet.='</row>';}$sheet.='</sheetData></worksheet>';
        $tmp=tempnam(Yii::getAlias('@runtime'),'xlsx');$zip=new \ZipArchive();$zip->open($tmp,\ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml','<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml','<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="KPI" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');$zip->addFromString('xl/worksheets/sheet1.xml',$sheet);$zip->close();$data=file_get_contents($tmp);unlink($tmp);
        return Yii::$app->response->sendContentAsFile($data,'kpi-hisobot.xlsx',['mimeType'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
