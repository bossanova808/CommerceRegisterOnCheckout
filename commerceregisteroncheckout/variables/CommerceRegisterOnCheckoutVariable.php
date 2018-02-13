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
     */
    public function checkoutRegistered(){

        $return = null;
        
        $registered = craft()->httpSession->get("registered");
        if(is_bool($registered)){
            $return = $registered;
        }

        return $return;
    }

    public function checkoutAccount(){

        $return = "";
        
        $account = craft()->httpSession->get("account");
        if($account) $return = $account;

        return $return;
    }
    
    public function clearRegisterSession(){
        craft()->httpSession->remove("registered");
        craft()->httpSession->remove("account");
    }


}
