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
    
    public function clearRegistered(){
        craft()->httpSession->remove("registered");
    }

    public function checkoutAccount(){

        $return = "";
        
        $account = craft()->httpSession->get("account");
        if($account) $return = $account;

        // For some reason these functions are called multiple times on the order complete template...
        // So we can't remove them here.        
        //craft()->httpSession->remove("account");

        return $return;
    }


}
