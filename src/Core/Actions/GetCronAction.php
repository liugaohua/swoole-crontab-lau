<?php
class GetCronAction extends BaseAction
{
   public function getResult()
   {
      $result = array();
      if( $id = $this->requestData[ 'id' ] )
      {
         $out = Common::getCronConfigInfo( $id );
         if( empty( $out ) )
         {
            $result = array(
                'type' => 2,
                'msg' => $id . '任务不存在'
            );
         }
         else
         {
            $result = array(
                'cronInfo' => $out,
                'ipHosts' => Common::getHosts(),
            );
         }
      }
      
      return $result;
   }
    
}