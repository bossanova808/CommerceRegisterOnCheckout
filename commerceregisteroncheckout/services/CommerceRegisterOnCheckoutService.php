<?php
namespace Craft;

class CommerceRegisterOnCheckoutService extends BaseApplicationComponent
{
    /**
     * Event method
     *
     * @param \CEvent $event
     *
     * @throws \CException
     */
    public function onRegisterComplete(\CEvent $event)
    {
        $this->raiseEvent('onRegisterComplete', $event);
    }
}