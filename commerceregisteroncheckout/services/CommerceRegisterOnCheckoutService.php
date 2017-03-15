<?php
namespace Craft;

class CommerceRegisterOnCheckoutService extends BaseApplicationComponent
{
    /**
     * Event method
     *
     * @param Commerce_OrderModel $order
     * @param UserModel $user
     *
     * @throws \CException
     */
    public function onRegisterComplete(Commerce_OrderModel $order, UserModel $user)
    {
        $this->raiseEvent('onRegisterComplete', new Event($this, array('order' => $order, 'user' => $user)));
    }
}