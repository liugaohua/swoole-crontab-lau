<?php

/**
 * Class BaseAction
 */
abstract class BaseAction
{
    /**
     * @var array
     */
    protected $requestData = array();

    /**
     * @var array
     */
    protected $swooleTable = array();

    /**
     * @param $requestData
     * @return $this
     */
    public function setRequestData( $requestData )
    {
        $this->requestData = $requestData;
        return $this;
    }

    /**
     * @param $swooleTable
     * @return $this
     */
    public function setSwooleTable( $swooleTable )
    {
        $this->swooleTable = $swooleTable;
        return $this;
    }
    
    abstract public function getResult();
    
    /**
     * 获取值为正整数的输入参数
     * @param	string	$key	字段名
     * @param	int	$defaultValue	默认值
     * @param	boolean	$isThrowException	是否抛出异常
     * @param	int	$errorCode	错误码
     * @return	int
     */
    public function getValueAsPositiveInteger( $key , $defaultValue = 0 , $isThrowException = false , $errorCode = 0 )
    {
        if( !isset( $this->requestData[$key] ) || ( $value = (integer)$this->requestData[$key] ) <= 0 )
        {
            $this->_tryThrowError( $key , $isThrowException , $errorCode );

            return $defaultValue;
        }

        return $value;
    }

    /**
     * 获取值为整数的输入参数
     * @param	string	$key	字段名
     * @param	int	$defaultValue	默认值
     * @param	boolean	$isThrowException	是否抛出异常
     * @param	int	$errorCode	错误码
     * @return	int
     */
    public function getValueAsInteger( $key , $defaultValue = 0 , $isThrowException = false , $errorCode = 0 )
    {
        if( !isset( $this->requestData[$key] ) )
        {
            $this->_tryThrowError( $key , $isThrowException , $errorCode );

            return $defaultValue;
        }

        return (integer)$this->requestData[$key];
    }

    /**
     * 获取值为前后没有空格的字符串的输入参数
     * @param    string $key 字段名
     * @param    string $defaultValue 默认值
     * @param    boolean $isThrowException 是否抛出异常
     * @param    int $errorCode 错误码
     * @param bool $filterHtml 是否过滤html标签
     * @param string $allowedTags 不过滤的html标签
     * @return string
     * @throws Exception
     */
    public function getValueAsTrimString( $key , $defaultValue = '' , $isThrowException = false , $errorCode = 0, $filterHtml = true , $allowedTags = '' )
    {
        if( !isset( $this->requestData[$key] ) || strlen( $value = $this->_filterHTMLOrNot( trim( $this->requestData[$key] ) , $filterHtml , $allowedTags ) ) <= 0 )
        {
            $this->_tryThrowError( $key , $isThrowException , $errorCode );

            return $defaultValue;
        }

        return $value;
    }

    /**
     * 获取值为字符串的输入参数
     * @param string $key 字段名
     * @param string $defaultValue 默认值
     * @param boolean $isThrowException 是否抛出异常
     * @param int $errorCode 错误码
     * @param bool $filterHtml 是否过滤html标签
     * @return string
     * @throws Exception
     */
    public function getValueAsNotTrimString( $key , $defaultValue = '' , $isThrowException = false , $errorCode = 0, $filterHtml = true )
    {
        if( !isset( $this->requestData[$key] ) || strlen( $value = $this->_filterHTMLOrNot( $this->requestData[$key] , $filterHtml ) ) <= 0 )
        {
            $this->_tryThrowError( $key , $isThrowException , $errorCode );

            return $defaultValue;
        }

        return $value;
    }

    /**
     * 获取一个由前后没有空格的字符串组成的数组
     * @param	string	$key	字段名
     * @param	string[]	$defaultValue	默认值
     * @param	boolean	$isThrowException	是否抛出异常
     * @param	int	$errorCode	错误码
     * @param	bool	$filterHtml	是否过滤html标签
     * @return	string[]
     */
    public function getValuesAsTrimStringArray( $key , $defaultValue = array() , $isThrowException = false , $errorCode = 0, $filterHtml = true )
    {
        if( !isset( $this->requestData[$key] ) || !is_array( $values = $this->requestData[$key] ) )
        {
            $this->_tryThrowError( $key , $isThrowException , $errorCode );

            return $defaultValue;
        }

        foreach( $values as $key => $value )
        {
            if( strlen( $values[$key] = $this->_filterHTMLOrNot( trim( $value ) , $filterHtml ) ) <= 0 )
            {
                unset( $values[$key] );
            }
        }

        if( count( $values ) <= 0 )
        {
            $this->_tryThrowError( $key , $isThrowException , $errorCode );

            return $defaultValue;
        }

        return $values;
    }
    
    /**
     * 过滤或不过滤HTML代码
     * @param    string $string 字符串
     * @param    bool $isFilter 是否过滤
     * @param string $allowedTags
     * @return string
     */
    private function _filterHTMLOrNot( $string , $isFilter = true , $allowedTags = '' )
    {
        if( $isFilter )
        {
            return strlen( $allowedTags ) > 0 ? strip_tags( $string , $allowedTags ) : strip_tags( $string );
        }

        return $string;
    }


    /**
     * 尝试报错
    /**
     * @param $key
     * @param bool $isThrowException
     * @param $errorCode
     * @throws Exception
     */
    private function _tryThrowError( $key , $isThrowException = false , $errorCode = 0 )
    {
        if( $isThrowException && $errorCode > 0 )
        {
            throw new Exception(  '['. $key .']参数不正确' , $errorCode );
        }
    }

}
