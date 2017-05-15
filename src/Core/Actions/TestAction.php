<?php
class TestAction extends BaseAction
{
    /**
     * @return array|string
     */
    public function getResult()
    {
        return Common::getLocalIpHost();
    }
}