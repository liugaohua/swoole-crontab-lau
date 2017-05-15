<?php

/**
 * 修改任务状态
 * @author songgl
 * @since 2017-03-20 15:20
 */
class ChangeStatusAction extends BaseAction
{
	public function getResult()
	{
		$id = $this->getValueAsPositiveInteger( 'id' , 0 );
		$status = $this->getValueAsPositiveInteger( 'status' , 0 );
		if( $id )
		{
			$sql = SQLBuilder::buildUpdateSQL(
				LoadTasksByMysqli::TABLE_NAME ,
				array(
					'status' => (int)$status ,
				) ,
				array(
					'id' => (int)$id ,
				)
			);
			LoadTasksByMysqli::getInstance()->query( $sql );
		}
		return array(
			'result' => true ,
			'status' => $status
		);
	}
}