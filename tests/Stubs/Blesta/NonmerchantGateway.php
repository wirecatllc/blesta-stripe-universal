<?php
abstract class NonmerchantGateway extends Gateway
{
    public function getCommonError($type)
    {
        return [$type => ['message' => $type]];
    }
}
