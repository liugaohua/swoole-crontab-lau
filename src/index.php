<?php

date_default_timezone_set('Asia/Shanghai');
define( 'APP_DEBUG', true );
define( 'DS', DIRECTORY_SEPARATOR );
define( 'ROOT_PATH', realpath( dirname( __FILE__ ) ) . DS );
define( 'LOG_DIR', __DIR__ . '/Logs/' );

require_once ROOT_PATH .'Core/Main.php';
Main::run();

