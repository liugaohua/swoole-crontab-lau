<?php
swoole_timer_tick(2000,function( $params ){
    print_r( $params );
    echo PHP_EOL;
    echo date( 'H:i:s').PHP_EOL;
});
