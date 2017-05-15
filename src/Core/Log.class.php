<?php

/**
 * Created by PhpStorm.
 * User: vic
 * Date: 15-12-30
 * Time: 上午11:23
 */
class Log
{
    /**
     * @var
     */
    static $logHandler;
    /**
     * @var
     */
    static $singleInstance;

    /**
     * 日志缓冲
     * @var array
     */
    public $_queue = array();

    private $_flush = false;
    
    function __construct()
    {
    }

    public function log( $file, $message, $extra = array() )
    {
        if( empty( $file ) && empty( $message ) && empty( $extra ) )
        {
            return;
        }
        $now = Common::getMTime();
        $destination = $file . '_' . date( 'Y-m-d' ) . '.log';

        if( !empty( $extra ) )
        {
            $message .= '; ';
            $message .= json_encode( $extra, JSON_UNESCAPED_UNICODE );
        }

        $message = sprintf( '%s# %s' . PHP_EOL, $now, $message );
        
        if( Main::getConfig( 'debug' ) )
        {
            if( Crontab::$daemon )
            {
                file_put_contents( $destination, $message, FILE_APPEND );
                if( filesize( $destination ) > 10485760 )
                {//10M
                    unlink( $destination );
                }
            }
            else
            {
                echo $message;
            }
        }
        else
        {
            $this->_queue[ $destination ][] = array(
                'destination' => $destination,
                'message' => $message,
            );
            if( count( $this->_queue ) > 10 || $this->_flush )
            {
                foreach( $this->_queue as $destination => $logs )
                {
                    if( filesize( $destination ) > 209715200 )
                    { //200M
                        rename( $destination, $destination . '.' . date( 'His' ) );
                    }
                    foreach( $logs as $message )
                    {
                        file_put_contents( $destination, $message, FILE_APPEND );
                    }
                }
                $this->_queue = array();
            }
        }
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