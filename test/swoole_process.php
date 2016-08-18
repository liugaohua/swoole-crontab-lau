<?php
echo posix_getpid();
echo PHP_EOL;
$worker_num =2;//创建的进程数
for($i=0;$i<$worker_num ; $i++){
    $process = new swoole_process('callback_function_we_write');
    $pid = $process->start();
    echo PHP_EOL . $pid;//
}
function callback_function_we_write(swoole_process $worker){
    echo  PHP_EOL;
    var_dump($worker);
    echo  PHP_EOL;
}