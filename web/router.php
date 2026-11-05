<?php
// Only for PHP's development server. Production document root must be web/.
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
$file=realpath(__DIR__.rawurldecode($path));
if($file && str_starts_with($file,__DIR__.DIRECTORY_SEPARATOR) && is_file($file) && !str_ends_with($file,'.php'))return false;
require __DIR__.'/index.php';
