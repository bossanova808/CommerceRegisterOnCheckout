<?php
/**
 * Commerce Register on Checkout plugin for Craft CMS
 *
 * Commerce Register on Checkout Variable
 *
 * @author    Jeremy Daalder
 * @copyright Copyright (c) 2016 Jeremy Daalder
 * @link      https://github.com/bossanova808
 * @package   CommerceRegisterOnCheckout
 * @since     0.0.1
 */

namespace Craft;

class CommerceRegisterOnCheckoutVariable
{
    /**
     * Returns the session data lodged during registration at checkout time
     *
     * @param $consume boolean - by default this data behaves like flash data, it is removed when it is read.  
     *                           You can pass in false to stop this happening
     * @return array ['registered'=>,'account'=>]
     */
    public function checkoutRegistration($consume=false){

        $return = [];
        
        $registered = craft()->httpSession->get("registered");
        if($registered) $return['registered'] = $registered;
        
        $account = craft()->httpSession->get("account");
        if($account) $return['account'] = $account;

        //This is problematic as we need to test if defined which triggers the consume...
        //but then we need to test the values, so leave it for now..
        // if($consume){
        //     craft()->httpSession->remove("registered");
        //     craft()->httpSession->remove("account");
        // }
        return $return;
    }
}