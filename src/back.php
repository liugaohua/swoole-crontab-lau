<?php

date_default_timezone_set('Asia/Shanghai');
define('APP_DEBUG', true);
define('DS', DIRECTORY_SEPARATOR);
define( 'ROOT_PATH', realpath( dirname( __FILE__ ) ) . DS );
define( 'LOG_DIR', __DIR__ . '/Logs/' );

require_once ROOT_PATH . 'Core/Main.php';
Main::spl_autoload_register();

$request = array();
if( isset( $GLOBALS[ 'argv' ] ) && count( $GLOBALS[ 'argv' ] ) > 1 )
{
    $cliArgument = $GLOBALS[ 'argv' ];
    array_shift( $cliArgument );
    parse_str( implode( '&' , $cliArgument ) , $request );
}

if( isset( $request[ 'err' ] ) )
{
    error_reporting( E_ALL ^ E_NOTICE );
}

try
{
    $responseMsg = Request::getInstance()
        ->setRequestData( $request )
        ->setIsBack( true )
        ->doRoute();
}
catch( Exception $e )
{
    $responseMsg = array(
        'code' => $e->getCode(),
        'msg' => $e->getMessage(),
        'request' => $request,
        'trace' => $e->getTraceAsString(),
    );
};
echo is_array( $responseMsg ) ? json_encode( $responseMsg ) : $responseMsg;
