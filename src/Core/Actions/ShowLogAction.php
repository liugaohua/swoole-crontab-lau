<?php
/**
 * Created by PhpStorm.
 * User: laugh
 * Date: 17/1/3
 * Time: 下午1:07
 */

class ShowLogAction extends BaseAction
{
    /**
     * @var array
     */
    private $_result = array();

    private $_cronInfo = array();
    
    public function getResult()
    {
        $id = $this->getValueAsPositiveInteger( 'id', 0 );
        if( $id <= 0 )
        {
            return $this->_result;
        }
        $logStr = $this->_getLogStr( $id );
        $this->_result['file'] = $logStr;
        if( !empty( $logStr ) && file_exists( $logStr ) )
        {
            $this->_result['logs'] = $this->tailSingleByte( $logStr, 100 );
        }
        return $this->_result;
    }

    /**
     * @param $id
     * @return array
     */
    private function _getLogStr( $id )
    {
        $this->_cronInfo = Common::getCronConfigInfo( $id );
        $cmd = $this->_cronInfo[ 'cmd' ] ?: '';
        if( empty( $cmd ) )
        {
            return $this->_result;
        }

        $redirectLogStr = '';
        if( ( $position = strpos( $cmd, '>>' ) ) !== false )
        {
            $redirectLogStr = trim( substr( $cmd, 2+$position ) );
            $redirectLogStr = explode( ' ', $redirectLogStr );
            $redirectLogStr = reset( $redirectLogStr );
        }
        if( empty( $redirectLogStr ) && ( $logStr = Main::getConfig( 'methodLogPath' ) ) )
        {
            if( preg_match( '#method=([\w.]+)#', $cmd, $method ) && !empty( $method[ 1 ] ) )
            {
                $logStr = $logStr . $method[ 1 ];
                return $logStr;
            }
        }
        return $redirectLogStr;
    }

    function tailSingleByte($file, $lines = 1) {
        $handle = fopen($file, "r");
        $linecounter = $lines;
        $pos = -2;
        $beginning = false;
        $text = array();
        while ($linecounter > 0) {
            $t = " ";
            while ($t != "\n") {
                if (fseek($handle, $pos, SEEK_END) == -1) {
                    $beginning = true;
                    break;
                }
                $t = fgetc($handle);
                $pos --;
            }
            $linecounter --;
            if ($beginning) rewind($handle);
            $text[$lines-$linecounter-1] = fgets($handle);
            if ($beginning) break;
        }
        fclose ($handle);
        return $text;
        return trim(implode("", array_reverse($text))); // array_reverse is optional: you can also just return the $text array which consists of the file's lines.
    }

}