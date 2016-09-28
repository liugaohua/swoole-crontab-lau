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
    /**
     * @var
     */
    static $logHandler;
    /**
     * @var
     */
    static $singleInstance;
    static $channel = 'cron';

    function __construct()
    {
    }

    private static function _init( $type )
    {
        self::$logHandler = new Monolog\Logger( self::$channel );
        self::$logHandler->pushHandler( new Monolog\Handler\StreamHandler( LOG_DIR . '/main.log', $type ) );
    }
    
    /**
     * @param $message
     * @param array $context
     */
    public static function warning( $message , $context = array())
    {
        self::_init( Monolog\Logger::WARNING );
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
    public static function error( $message, $context = array() )
    {
        self::_init( Monolog\Logger::ERROR );
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
    public static function debug( $message, $context = array() )
    {
        self::_init( Monolog\Logger::DEBUG );
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
        self::_init( Monolog\Logger::NOTICE );
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
    public static function info( $message, $context = array() )
    {
        self::_init( Monolog\Logger::INFO );
        if( empty( $message ) && empty( $context ) )
        {
            return;
        }
        self::$logHandler->info( $message, $context );
    }

    /**
     * @return Log|null
     */
    public static function getInstance()
    {
        if( !isset( self::$singleInstance ) || !is_object( self::$singleInstance ) )
        {
            self::$singleInstance = new self();
        }
        return self::$singleInstance;
    }
}