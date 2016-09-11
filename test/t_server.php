<?php
swoole_timer_tick( 2000, function()
{
    echo __LINE__.PHP_EOL;
} );
return;
/*$socket = stream_socket_server("tcp://0.0.0.0:8080", $errno, $errstr);
if (!$socket) {
    echo "$errstr ($errno)<br />\n";
} else {
    while ($conn = stream_socket_accept($socket)) {
        fwrite($conn, 'The local time is ' . date('n/j/Y g:i a') . "\n");
        fclose($conn);
    }
    fclose($socket);
}*/

