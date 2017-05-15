<?php

/**
 * Class AddCronAction
 */
class AddCronAction extends BaseAction
{

    public function getResult()
    {
        $data = array();
        $data[ 'id' ] = $this->getValueAsPositiveInteger( 'id', 0 );
        $data[ 'taskid' ] = $this->getValueAsTrimString( 'taskid' );
        if( $taskname = $this->getValueAsTrimString( 'taskname', '', true, Request::EXCEPTION_CODE_PARAM_ERROR ) )
        {
            $data['taskname'] = $taskname;
        }

        if( ( $unique = $this->getValueAsPositiveInteger( 'unique', 1, false , Request::EXCEPTION_CODE_PARAM_ERROR ) ) )
        {
            $data[ 'unique' ] = min( $unique, 20 );
        }

        #if( ( $rule = $this->getValueAsTrimString( 'rule', '', true, Request::EXCEPTION_CODE_PARAM_ERROR ) ) && ParseCrontab::parse( $rule ) )
        if( ( $rule = $this->getValueAsTrimString( 'rule', '', true, Request::EXCEPTION_CODE_PARAM_ERROR ) ) ) 
        {
            $data[ 'rule' ] = $rule;
        }

        if( $cmd = $this->getValueAsTrimString( 'cmd', '', true, Request::EXCEPTION_CODE_PARAM_ERROR ) )
        {
            $data[ 'args' ] = json_encode(
                array(
                    'cmd' => $cmd,
                    'ext' => '',
                )
            );
        }
        $data[ 'execute' ] = 'Cmd';
        $data[ 'status' ] = (int)$this->getValueAsPositiveInteger( 'status', 0 );
        if( $data['id'] > 0 )
        {
            $data[ 'updatetime' ] = date( 'Y-m-d H:i:s', time() );
        }
        else
        {
            $data[ 'updatetime' ] = date( 'Y-m-d H:i:s', time() );
            $data[ 'createtime' ] = date( 'Y-m-d H:i:s', time() );
        }

        $onDuplicatedDatas = $data;
        $mysqlHandle = new LoadTasksByMysqli();
        if( isset( $onDuplicatedDatas[ 'id' ] ) )
        {
            unset( $onDuplicatedDatas[ 'id' ] );
        }
        
        $sql = SQLBuilder::buildInsertOrUpdateSQL(
            LoadTasksByMysqli::TABLE_NAME,
            $data,
            $onDuplicatedDatas
        );
        if( $mysqlHandle->query( $sql ) )
        {
            return array(
                'status' => true,
                'id' => $mysqlHandle->getLastId()
            );
        }
        return array( 'status' => false );
    }
}