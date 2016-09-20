<?php

/**
 * Created by PhpStorm.
 * User: vic
 * Date: 15-12-30
 * Time: 上午11:23
 */
class Log
{
    public $level;
    public $type;
    public $logpath;
    static $logHandler;
    static $singleInstance = array();

    function __construct( $channel )
    {
        self::$logHandler = new Monolog\Logger( $channel );
        self::$logHandler->pushHandler( new Monolog\Handler\StreamHandler( LOG_DIR . '/main.log', Monolog\Logger::WARNING ) );
    }

    /**
     * @param $message
     * @param array $context
     */
    public function warning( $message , $context = array())
    {
        if( empty( $message ) && empty( $context ) )
        {
            return;
        }
        self::$logHandler->warning( $message, $context );
    }

    /**
     * @param $message
     * @param array $context
     */
    public function error( $message, $context = array() )
    {
        if( empty( $message ) && empty( $context ) )
        {
            return;
        }
        self::$logHandler->error( $message, $context );
    }

    /**
     * @param $message
     * @param array $context
     */
    public function debug( $message, $context = array() )
    {
        if( empty( $message ) && empty( $context ) )
        {
            return;
        }
        self::$logHandler->debug( $message, $context );
    }

    /**
     * @param $message
     * @param array $context
     */
    public function notice( $message, $context = array() )
    {
        if( empty( $message ) && empty( $context ) )
        {
            return;
        }
        self::$logHandler->notice( $message, $context );
    }

    /**
     * @param $message
     * @param array $context
     */
    public function info( $message, $context = array() )
    {
        if( empty( $message ) && empty( $context ) )
        {
            return;
        }
        self::$logHandler->info( $message, $context );
    }

    /**
     * @param string $channel
     * @return Log|null
     */
    public static function getInstance( $channel = 'cron' )
    {
        if( !isset( self::$singleInstance[ $channel ] ) || !is_object( self::$singleInstance[ $channel ] ) )
        {
            self::$singleInstance[ $channel ] = new self( $channel );
        }
        return self::$singleInstance[ $channel ];
    }
}