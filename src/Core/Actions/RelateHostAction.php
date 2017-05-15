<?php

/**
 * 配置任务关联主机
 * Class RelateHostAction
 */
class RelateHostAction extends BaseAction
{

    public function getResult()
    {
        $ipHosts = $this->getValuesAsTrimStringArray( 'ipHosts', array() );
        $id = $this->getValueAsPositiveInteger( 'id', 0, true, Request::EXCEPTION_CODE_PARAM_ERROR );
        $status = false ;
        if( !empty( $ipHosts ) && $id > 0 )
        {
            $successHosts = array();
            $mysqliHandle = LoadTasksByMysqli::getInstance();
            foreach( $ipHosts as $ipHost )
            {
                if( Common::checkIpHost( $ipHost, false ) )
                {
                    $data = array(
                            'ipHost' => ip2long( $ipHost ),
                            'id' => $id,
                        );
                    $sql = SQLBuilder::buildInsertOrUpdateSQL( LoadTasksByMysqli::HOST_CRON_RELATED, $data );
                    if( !$mysqliHandle->query( $sql ) )
                    {
                        $status = false;
                    }
                    else
                    {
                        $status = true;
                        $successHosts[] = ip2long( $ipHost );
                    }
                }
            }

            $sql = SQLBuilder::buildDeleteSQL(
                LoadTasksByMysqli::HOST_CRON_RELATED,
                array(
                    'id' => $id,
                    'ipHost' => array(
                        'operation' => SQLBuilder::OPERATION_NOT_IN,
                        'value' => $successHosts,
                    )
                )
            );
            $mysqliHandle->query( $sql );
        }
        return array(
            'status' => $status,
        );
    }
}