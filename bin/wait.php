<?php
$context=stream_context_create(['http'=>['timeout'=>1,'ignore_errors'=>true]]);
for($attempt=0;$attempt<30;$attempt++){
    $body=@file_get_contents('http://127.0.0.1/index.php?r=auth/login',false,$context);
    if($body!==false && str_contains($body,'Tizimga kirish')){echo "KPI web xizmati tayyor.\n";exit(0);}
    sleep(1);
}
fwrite(STDERR,"KPI web xizmati 30 urinishda tayyor bo‘lmadi. docker compose logs app buyrug‘ini tekshiring.\n");exit(1);
