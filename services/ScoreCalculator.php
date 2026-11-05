<?php
namespace app\services;
use DomainException;
class ScoreCalculator
{
    /** Decimal scores are calculated as hundredths to avoid float accumulation. */
    public static function calculate(array $rule, int $quantity, $expertScore, int $usedCents = 0, int $approvedCount = 0, $metricValue=null): int
    {
        if (!$rule['enabled']) throw new DomainException('Bu ko‘rsatkich qoidasi administrator qarorini kutmoqda.');
        if ($quantity < 1 || $quantity > 100000) throw new DomainException('Miqdor 1–100000 oralig‘idagi butun son bo‘lishi kerak.');
        if ($rule['once_only'] && ($quantity !== 1 || $approvedCount > 0)) throw new DomainException('Bu ko‘rsatkich davrda faqat bir marta hisoblanadi.');
        $type = $rule['calc_type'];
        if (in_array($type, ['PulFormulasi','XirshIndeksi'], true)) {
            if ($quantity!==1 || $metricValue===null || !is_numeric($metricValue) || (float)$metricValue<0 || (float)$metricValue>1000000000000) throw new DomainException('Formula uchun qiymat 0–1 000 000 000 000 oralig‘ida bo‘lsin, miqdor esa 1.');
            $units=(float)$metricValue;
            if($type==='PulFormulasi'){
                if(empty($rule['formula_divisor']) || (float)$rule['formula_divisor']<=0 || !in_array($rule['formula_mode']??'',['proportional','floor'],true)) throw new DomainException('Grant normativi va hisoblash usuli belgilanmagan.');
                $units/=(float)$rule['formula_divisor'];if($rule['formula_mode']==='floor')$units=floor($units);
            }elseif(floor($units)!==$units)throw new DomainException('Xirsh qiymati butun son bo‘lsin.');
            $raw=$units*(float)$rule['base_score']*100;
            if(!is_finite($raw) || $raw>2000000000)throw new DomainException('Hisoblangan ball ruxsat etilgan texnik chegaradan oshdi. Qoida va qiymatni tekshiring.');
            $amount=(int)round($raw);
            if($rule['cap_score']!==null && $rule['cap_score']!=='')$amount=max(0,min($amount,(int)round((float)$rule['cap_score']*100)-$usedCents));
            return $amount;
        }
        if (in_array($type, ['Ballgacha','DarajaShkalasi'], true)) {
            if ($quantity !== 1 || $expertScore === null || $expertScore === '' || !is_numeric($expertScore)) throw new DomainException('Ekspert bahosi talab qilinadi.');
            $cents = (int)round((float)$expertScore * 100);
            if ($cents < 0 || $cents > (int)round((float)$rule['upper_score'] * 100)) throw new DomainException('Ekspert bahosi belgilangan chegaradan tashqarida.');
            return $cents;
        }
        if (!in_array($type, ['QatiyBall','BirlikUchun','ChegaraliYigindi'], true)) throw new DomainException('Hisoblash turi noma’lum.');
        $amount = (int)round((float)$rule['base_score'] * 100) * $quantity;
        if($amount>2000000000)throw new DomainException('Hisoblangan ball juda katta. Qoida va miqdorni tekshiring.');
        if ($rule['cap_score'] !== null && $rule['cap_score'] !== '') $amount = max(0, min($amount, (int)round((float)$rule['cap_score']*100) - $usedCents));
        return $amount;
    }
}
