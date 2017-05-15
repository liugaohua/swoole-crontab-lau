<?php
class CheckRuleAction extends BaseAction
{
    public function getResult()
    {
        $rule = $this->getValueAsTrimString( 'rule' );
        return array(
            'rule' => $rule,
            'status' => ParseCrontab::parse( $rule ),
        );
    }
}