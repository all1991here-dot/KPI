<?php
/** Local file updater only: no application bootstrap, database writes or network. */
declare(strict_types=1);

const ACADEMIC_FILES = [
    'controllers/KpiController.php', 'services/Dashboard.php', 'services/Ui.php',
    'views/layouts/main.php', 'views/auth/login.php', 'views/kpi/index.php',
    'web/css/design.css', 'web/css/academic.css', 'web/js/app.js',
    'web/images/fardu-campus.jpg', 'web/images/fardu-logo.png',
];

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});

function academicInside(string $path, string $root): bool
{
    $path = str_replace('\\', '/', $path);
    $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
    if (PHP_OS_FAMILY === 'Windows') { $path = strtolower($path); $root = strtolower($root); }
    return str_starts_with($path . '/', $root);
}

function academicParent(string $file, string $root, bool $create): void
{
    $directory = dirname($file);
    $existing = $directory;
    while (!is_dir($existing) && dirname($existing) !== $existing) $existing = dirname($existing);
    $real = realpath($existing);
    if ($real === false || !academicInside($real, $root)) throw new RuntimeException('Papka dastur ildizidan tashqariga chiqadi: ' . $directory);
    if (!is_writable($existing)) throw new RuntimeException('Papkaga yozish mumkin emas: ' . $existing);
    if ($create && !is_dir($directory) && !mkdir($directory, 0775, true)) throw new RuntimeException('Papka yaratilmadi: ' . $directory);
}

function academicCopy(string $source, string $destination): void
{
    if (!copy($source, $destination)) throw new RuntimeException('Fayl kochirilmadi: ' . $destination);
    if (!hash_equals(hash_file('sha256', $source), hash_file('sha256', $destination))) throw new RuntimeException("Kochirilgan fayl tekshiruvi otmadi: " . $destination);
}

$changed = [];
$originals = [];
$backup = null;
$log = null;
$target = null;

try {
    $target = isset($argv[1]) ? realpath($argv[1]) : false;
    $source = __DIR__;
    if (!$target || !is_dir($target)) throw new RuntimeException('Mavjud KPI papkasi topilmadi.');
    foreach (['start-native.bat', 'vendor/autoload.php', 'config/common.php', 'views/layouts/main.php'] as $required) {
        if (!is_file($target . '/' . $required)) throw new RuntimeException('Tanlangan papka mavjud KPI dasturi emas. Fayl topilmadi: ' . $required);
    }
    $manifestFile = $source . '/files.sha256';
    if (!is_file($manifestFile)) throw new RuntimeException('files.sha256 topilmadi. ZIPni toliq yangi papkaga oching, BATni ZIP ichidan ishga tushirmang.');
    $manifest = [];
    foreach (file($manifestFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (!preg_match('~^([a-f0-9]{64})  KPI/(.+)$~', $line, $match)) throw new RuntimeException('files.sha256 formati notogri. Paketni qayta yuklang.');
        if (!in_array($match[2], ACADEMIC_FILES, true) || isset($manifest[$match[2]])) throw new RuntimeException('Paketda kutilmagan yoki takroriy fayl bor: ' . $match[2]);
        $manifest[$match[2]] = $match[1];
    }
    if (count($manifest) !== count(ACADEMIC_FILES)) throw new RuntimeException('Paket manifesti toliq emas. ZIPni qayta yuklang.');
    // Validate every payload and destination before changing any application file.
    foreach (ACADEMIC_FILES as $relative) {
        $payload = $source . '/KPI/' . $relative;
        $destination = $target . '/' . $relative;
        if (!is_file($payload) || is_link($payload)) throw new RuntimeException('Paket fayli topilmadi: KPI/' . $relative . '. ZIPni toliq oching.');
        if (!hash_equals($manifest[$relative], hash_file('sha256', $payload))) throw new RuntimeException('Paket fayli buzilgan: KPI/' . $relative . '. ZIPni qayta yuklang.');
        if (is_link($destination) || (file_exists($destination) && !is_file($destination))) throw new RuntimeException('Manzilda oddiy fayl orniga papka yoki havola bor: ' . $relative);
        if (is_file($destination) && !is_writable($destination)) throw new RuntimeException('Faylga yozish mumkin emas: ' . $relative . '. Fayl ruxsatlarini tekshiring.');
        academicParent($destination, $target, false);
    }
    academicParent($target . '/runtime/install-check.tmp', $target, false);
    $backup = $target . '/runtime/academic-backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4));
    if (!mkdir($backup, 0700, true)) throw new RuntimeException('Zaxira papkasi yaratilmadi.');
    $log = $backup . '/install.log';
    file_put_contents($log, "FarDU academic installer 2\nTarget: " . $target . "\n");
    echo "Paketning 11 ta fayli tekshirildi.\n";
    foreach (ACADEMIC_FILES as $relative) {
        $destination = $target . '/' . $relative;
        $originals[$relative] = is_file($destination);
        if ($originals[$relative]) {
            $saved = $backup . '/originals/' . $relative;
            if (!is_dir(dirname($saved))) mkdir(dirname($saved), 0700, true);
            academicCopy($destination, $saved);
        }
    }
    file_put_contents($backup . '/originals.json', json_encode($originals, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    foreach (ACADEMIC_FILES as $relative) {
        $destination = $target . '/' . $relative;
        academicParent($destination, $target, true);
        $changed[] = $relative;
        academicCopy($source . '/KPI/' . $relative, $destination);
        file_put_contents($log, 'OK ' . $relative . "\n", FILE_APPEND);
        echo 'OK: ' . $relative . "\n";
    }
    file_put_contents($log, "SUCCESS\n", FILE_APPEND);
    echo "\nTAYYOR. Akademik dizayn ornatildi.\nBrauzerda Ctrl+F5 bosing.\nZaxira: " . $backup . "\nBaza, parollar va hujjatlar almashtirilmadi.\n";
    exit(0);
} catch (Throwable $error) {
    $rollbackErrors = [];
    foreach (array_reverse($changed) as $relative) {
        try {
            if ($originals[$relative]) academicCopy($backup . '/originals/' . $relative, $target . '/' . $relative);
            elseif (is_file($target . '/' . $relative) && !unlink($target . '/' . $relative)) throw new RuntimeException('Yangi fayl olib tashlanmadi: ' . $relative);
        } catch (Throwable $rollback) { $rollbackErrors[] = $relative . ': ' . $rollback->getMessage(); }
    }
    $message = "XATO: " . $error->getMessage() . "\n";
    if (!$changed) $message .= "Dastur fayllari ozgartirilmadi.\n";
    elseif (!$rollbackErrors) $message .= "Yangilangan fayllar avvalgi holatiga qaytarildi.\n";
    else $message .= "Ayrim fayllarni qaytarish tugamadi:\n" . implode("\n", $rollbackErrors) . "\n";
    if ($backup) $message .= 'Zaxira: ' . $backup . "\n";
    if ($log) {
        try { file_put_contents($log, $message, FILE_APPEND); } catch (Throwable $ignored) {}
        $message .= 'Tafsilot: ' . $log . "\n";
    }
    fwrite(STDERR, "\n" . $message . "Qora oynaning shu xabarini yordam uchun yuboring.\n");
    exit(1);
}
