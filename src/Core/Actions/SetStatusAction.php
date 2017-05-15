<?php

/**
 * Class SetStatusAction
 */
class SetStatusAction extends BaseAction
{

    /**
     * @return array
     * @throws Exception
     */
    public function getResult()
    {
        $taskId = $this->getValueAsPositiveInteger( 'taskId', 0 );
        $status = $this->getValueAsPositiveInteger( 'status', 0 );
        $sql ='';
        if( $taskId )
        {
            $sql = SQLBuilder::buildUpdateSQL(
                LoadTasksByMysqli::TABLE_NAME,
                array(
                    'status' => $status,
                ),
                array(
                    'taskid' => $taskId,
                )
            );
            LoadTasksByMysqli::getInstance()->query( $sql );
        }
        return array(
            'result' => $sql,
        );
    }
}