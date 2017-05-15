<?php

/**
 * @about 删除任务
 * Class RemoveCronAction
 */
class RemoveCronAction extends BaseAction
{
    public function getResult()
    {
        $result = array();
        if( $id = $this->requestData[ 'id' ] )
        {
            $sql = SQLBuilder::buildDeleteSQL(
                LoadTasksByMysqli::TABLE_NAME,
                array(
                    'id' => $id,
                )
            );
            $mysqlHandle = LoadTasksByMysqli::getInstance();
            $cronResult = $mysqlHandle->query( $sql );
            $sql = SQLBuilder::buildDeleteSQL(
                LoadTasksByMysqli::HOST_CRON_RELATED,
                array(
                    'id' => $id,
                )
            );
            $hostResult = $mysqlHandle->query( $sql );
            $result = array(
                'cronResult' => $cronResult,
                'hostResult' => $hostResult,
            );
        }

        return $result;
    }

}
