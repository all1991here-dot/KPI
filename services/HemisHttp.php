<?php
namespace app\services;

use DomainException;

/** HTTP transport. Never include response bodies or credentials in error messages. */
class HemisHttp
{
    public static function get(string $url, string $token): array
    {
        if (!extension_loaded('curl')) throw new DomainException('PHP cURL kengaytmasi yoqilmagan.');
        $curl = curl_init($url);
        $data = '';
        $tooLarge = false;
        try {
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 45, CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $token],
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            ]);
            $ca = getenv('KPI_CA_BUNDLE');
            if ($ca && (!is_file($ca) || !is_readable($ca))) {
                throw new DomainException('KPI_CA_BUNDLE sertifikat fayli topilmadi yoki oqib bolmaydi.');
            }
            if ($ca) curl_setopt($curl, CURLOPT_CAINFO, $ca);
            elseif (PHP_OS_FAMILY === 'Windows') {
                $bundle = dirname(__DIR__) . '/resources/tls/cacert.pem';
                if (!is_file($bundle)) throw new DomainException('SSL sertifikat paketi topilmadi. HEMIS yangilanishini toliq ornating.');
                curl_setopt($curl, CURLOPT_CAINFO, $bundle);
                if (defined('CURLSSLOPT_NATIVE_CA')) curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
            }
            curl_setopt($curl, CURLOPT_WRITEFUNCTION, static function ($handle, string $chunk) use (&$data, &$tooLarge): int {
                if (strlen($data) + strlen($chunk) > 8 * 1024 * 1024) { $tooLarge = true; return 0; }
                $data .= $chunk;
                return strlen($chunk);
            });
            $ok = curl_exec($curl);
            $errno = curl_errno($curl);
            $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($tooLarge) throw new DomainException('HEMIS javobi 8 MB chegaradan oshdi. Natija qollanmadi.');
            if ($ok === false) {
                $reason = match ($errno) {
                    5 => 'Proksi server topilmadi. Proksi sozlamalarini tekshiring.',
                    6 => 'Domen DNS orqali topilmadi. Domen yozilishi va internetni tekshiring.',
                    7 => 'Serverga ulanib bolmadi. Internet, VPN yoki server tarmoq ruxsatini tekshiring.',
                    28 => 'Ulanish vaqti tugadi. Internet yoki server javobini tekshiring.',
                    35 => 'SSL/TLS ulanishi ornatilmadi. Server sertifikati va kompyuter sanasini tekshiring.',
                    60 => 'SSL sertifikati tasdiqlanmadi. Kompyuter sanasi, server sertifikat zanjiri va ishonchli CA paketini tekshiring.',
                    77 => 'SSL CA sertifikat faylini oqib bolmadi. KPI_CA_BUNDLE yoki yangilanish paketini tekshiring.',
                    default => 'Tarmoq ulanishi uzildi. Internet, proksi va server holatini tekshiring.',
                };
                throw new DomainException('HEMIS: ' . $reason . ' (cURL ' . $errno . '). API kaliti saqlangan holatda qoldi.');
            }
            if ($status !== 200) {
                $reason = match ($status) {
                    401 => 'API kaliti qabul qilinmadi. Kalitni yangilang.',
                    403 => 'Kirish taqiqlandi. API kaliti huquqi yoki IP ruxsatini HEMIS administratori bilan tekshiring.',
                    404 => 'REST API manzili topilmadi. Student kabineti emas, API berilgan HEMIS backend domenini kiriting.',
                    301, 302, 303, 307, 308 => 'Manzil boshqa sahifaga yonaltirmoqda. API berilgan aniq backend domenini kiriting.',
                    429 => 'Sorovlar chegarasi oshdi. Birozdan keyin qayta urinib koring.',
                    default => $status >= 500 ? 'HEMIS serverida xato. Keyinroq qayta urinib koring.' : 'Server kutilmagan HTTP javob qaytardi.',
                };
                throw new DomainException('HEMIS (HTTP ' . $status . '): ' . $reason);
            }
            try { $json = json_decode($data, true, 64, JSON_THROW_ON_ERROR); }
            catch (\JsonException $e) { throw new DomainException('HEMIS JSON orniga boshqa javob yubordi. API backend domenini tekshiring.'); }
            if (!is_array($json)) throw new DomainException('HEMIS javob tuzilmasi kutilgan formatda emas.');
            return $json;
        } finally { curl_close($curl); }
    }
}
