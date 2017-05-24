<?php
/**
 * Commerce Register on Checkout plugin for Craft CMS
 *
 * Register customers on checkout with Craft Commerce
 *
 * @author    Jeremy Daalder
 * @copyright Copyright (c) 2016 Jeremy Daalder
 * @link      https://github.com/bossanova808
 * @package   CommerceRegisterOnCheckout
 * @since     0.0.1
 */

namespace Craft;

class CommerceRegisterOnCheckoutPlugin extends BasePlugin
{

    protected static $settings;

    /**
     * Static log functions for this plugin
     *
     * @param mixed $msg
     * @param string $level
     * @param bool $force
     *
     * @return null
     */
    public static function logError($msg){
        CommerceRegisterOnCheckoutPlugin::log($msg, LogLevel::Error, $force = true);
    }
    public static function logWarning($msg){
        CommerceRegisterOnCheckoutPlugin::log($msg, LogLevel::Warning, $force = true);
    }
    // If debugging is set to true in this plugin's settings, then log every message, devMode or not.
    public static function log($msg, $level = LogLevel::Info, $force = false)
    {
        if(self::$settings['debug']) $force=true;

        if (is_string($msg))
        {
            $msg = "\n\n" . $msg . "\n";
        }
        else
        {
            $msg = "\n\n" . print_r($msg, true) . "\n";
        }

        parent::log($msg, $level, $force);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
         return Craft::t('Commerce Register on Checkout');
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return Craft::t("Commerce Register on Checkout lets you offer user registration during checkout with Craft Commerce.");
    }

    /**
     * @return string
     */
    public function getDocumentationUrl()
    {
        return 'https://github.com/bossanova808/commerceregisteroncheckout/blob/master/README.md';
    }

    /**
     * @return string
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/bossanova808/commerceregisteroncheckout/master/releases.json';
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return '0.0.11';
    }

    /**
     * @return string
     */
    public function getSchemaVersion()
    {
        return '0.0.2';
    }

    /**
     * @return string
     */
    public function getDeveloper()
    {
        return 'Jeremy Daalder';
    }

    /**
     * @return string
     */
    public function getDeveloperUrl()
    {
        return 'https://github.com/bossanova808';
    }

    public function getSettingsHtml()
    {

        $settings = self::$settings;

        $variables = array(
            'name'     => $this->getName(true),
            'version'  => $this->getVersion(),
            'settings' => $settings,
            'description' => $this->getDescription(),
        );

        return craft()->templates->render('commerceregisteroncheckout/_settings', $variables);

   }

    public function defineSettings()
    {
        return array(
            'debug' => AttributeType::Bool,
        );
    }

    /**
     * @return bool
     */
    public function hasCpSection()
    {
        return false;
    }

    /**
     */
    public function onBeforeInstall()
    {
        craft()->db->createCommand()->createTable("commerceregisteroncheckout",["orderNumber"=>"varchar","EPW"=>"varchar", "lastUsedShippingAddressId"=>"integer", "lastUsedBillingAddressId" => "integer"]);        
    }

    /**
     */
    public function onAfterInstall()
    {
    }

    /**
     */
    public function onBeforeUninstall()
    {
       craft()->db->createCommand()->dropTable("commerceregisteroncheckout"); 
    }

    /**
     */
    public function onAfterUninstall()
    {
    }

    /* 
     * Clean up the registration records in the DB - for the current order, and for any incomplete carts older than the purge duration
    */
    private function cleanUp($order){

        // Delete the DB record for this order
        craft()->db->createCommand()->delete("commerceregisteroncheckout", array("orderNumber" => $order->number));

        // Also take the chance to clean out any old order records that are associated with incomplete carts older than the purge duration
        // Code from getCartsToPurge in Commerce_CartService.php

        $configInterval = craft()->config->get('purgeInactiveCartsDuration', 'commerce');
        $edge = new DateTime();
        $interval = new DateInterval($configInterval);
        $interval->invert = 1;
        $edge->add($interval);
        
        // Added this...
        $mysqlEdge = $edge->format('Y-m-d H:i:s');
        craft()->db->createCommand()->delete("commerceregisteroncheckout", "`dateUpdated` <= :mysqlEdge", array(':mysqlEdge' => $mysqlEdge));

    }


    /**
     * @return mixed
     */
    public function init(){

        self::$settings = $this->getSettings();

        // Listen to onOrderComplete (not onBefore...) as we definitely don't want to make submitting orders have more potential issues...
        // We check our DB for a registration record, if there is one, we complete registration & for security delete the record
        craft()->on('commerce_orders.onOrderComplete', function($event){

            $order = $event->params['order'];

            //Get all records, latest first
            $result = craft()->db->createCommand()->select()->from("commerceregisteroncheckout")->where(array("orderNumber" => $order->number))->order(array("dateUpdated DESC"))->queryAll();

            // Short circuit if we don't have registration details for this order
            if (!$result){
                CommerceRegisterOnCheckoutPlugin::log("Register on checkout record not found for order: " . $order->number);
                return true;
            }
                
            CommerceRegisterOnCheckoutPlugin::log("Register on checkout record FOUND for order: " . $order->number);

            // Clean up the DB so we're not keeping even encrypted passwords around for any longer than is necessary
            $this->cleanup($order);

            // Retrieve and decrypt the stored password, short circuit if we can't get it...           
            try {
                //refer only to the latest record
                $encryptedPassword = $result[0]['EPW'];
                $password = craft()->security->decrypt(base64_decode($encryptedPassword));
            }
            catch (Exception $e) {
                CommerceRegisterOnCheckoutPlugin::logError("Couldn't retrieve registration password for order: " . $order->number);
                CommerceRegisterOnCheckoutPlugin::logError($e);
                return false;                   
            }
            
            //Grab our other saved data
            try {
                $lastUsedShippingAddressId = $result[0]['lastUsedShippingAddressId'];
                $lastUsedBillingAddressId = $result[0]['lastUsedBillingAddressId'];
            }
            catch (Exception $e) {
                CommerceRegisterOnCheckoutPlugin::logError("Couldn't retrieve the lastUsedAddress Ids");
                CommerceRegisterOnCheckoutPlugin::logError($e);
                $lastUsedShippingAddressId = 0;
                $lastUsedBillingAddressId = 0;                  
            }

            $firstName = "";
            $lastName = "";     

            //Is there a billing address?  If so by default use that
            $address = $order->getBillingAddress();
            if($address){
                $firstName = $address->firstName;
                $lastName = $address->lastName;
            }

            //Overrule with POST data if that's supplied instead (this won't work with offiste gateways like PayPal though)
            if(craft()->request->getParam('firstName')){
                $firstName = craft()->request->getParam('firstName');
            }
            if(craft()->request->getParam('lastName')){
                $lastName = craft()->request->getParam('lastName');
            }                


            //@TODO - we offer only username = email support currently - since in Commerce everything is keyed by emails...
            $user = new UserModel();
            $user->username         = $order->email;
            $user->email            = $order->email;
            $user->firstName        = $firstName;
            $user->lastName         = $lastName;
            $user->newPassword      = $password;

            craft()->commerceRegisterOnCheckout->onBeforeRegister($order, $user);

            $success = craft()->users->saveUser($user);

            if ($success) {
                CommerceRegisterOnCheckoutPlugin::log("Registered new user $address->firstName $address->lastName [$order->email] on checkout");

                // Assign them to the default user group (customers)
                craft()->userGroups->assignUserToDefaultGroup($user);
                // & Log them in
                craft()->userSession->loginByUserId($user->id);
                // & record we've done this so the template variable can be set
                craft()->httpSession->add("registered", true);

                //Try & copy the last used addresses into the new record
                $res = craft()->db->createCommand()->select()->from("commerce_customers")->where(array("email" => $order->email))->order(array("dateUpdated DESC"))->queryAll();

                //CommerceRegisterOnCheckoutPlugin::log($res);

                if ($res) {
                    // $id will be the new customer record Id,  $oldId is the old customer record Id.
                    try{
                        $newId = $res[0]['id'];
                        $oldId = $res[1]['id'];
                        // ....the new Craft user id
                        $userId = $res[0]['userId'];
                        CommerceRegisterOnCheckoutPlugin::log("Updating customer and address records for newly created user.  NewId: $newId, OldId: $oldId, Craft User id: $userId");
                    }
                    catch (Exception $e) {
                        CommerceRegisterOnCheckoutPlugin::logError("Issue retrieving newId, oldId, or userId  - can't update addresses to new user.");  
                        CommerceRegisterOnCheckoutPlugin::logError($e);
                        // User registration did work, though....
                        craft()->commerceRegisterOnCheckout->onRegisterComplete($order, $user);
                        return true;
                    }

                    // First try and update the last used addresses in new record in the commerce_customers table
                    try {
                        $updateResult = craft()->db->createCommand()->update('commerce_customers',['lastUsedShippingAddressId'=>$lastUsedShippingAddressId, "lastUsedBillingAddressId"=>$lastUsedBillingAddressId], 'id=:id', array(':id'=>$newId));
                        CommerceRegisterOnCheckoutPlugin::log("Updated ($updateResult) customer records. To lusaId: $lastUsedShippingAddressId, lubaId: $lastUsedBillingAddressId");
                    }
                    catch (Exception $e) {
                        CommerceRegisterOnCheckoutPlugin::logError("Couldn't update the lastUsedAddress Ids");  
                        CommerceRegisterOnCheckoutPlugin::logError($e);             
                    } 
                    
                    // Now try and update the commerce_customers_addresses table and move the address records over to the 
                    try {
                        $updateResult = craft()->db->createCommand()->update('commerce_customers_addresses',['customerId'=>$newId],'customerId=:oldId', array(':oldId'=>$oldId));  

                        CommerceRegisterOnCheckoutPlugin::log("Updated ($updateResult) address records. From customer id: $oldId to new id: $newId");                   
                    }              
                    catch (Exception $e) {
                        CommerceRegisterOnCheckoutPlugin::logError("Couldn't transfer addresses to the new user id");  
                        CommerceRegisterOnCheckoutPlugin::logError($e);  
                    }  
            
                }
                else {
                    CommerceRegisterOnCheckoutPlugin::logError("Couldn't find the guest user for the lastUsedAddress Ids");
                }

                craft()->commerceRegisterOnCheckout->onRegisterComplete($order, $user);
                return true;
            }

            //If we haven't returned already, registration failed....
            CommerceRegisterOnCheckoutPlugin::logError("Failed to register new user $address->firstName $address->lastName [$order->email] on checkout");
            CommerceRegisterOnCheckoutPlugin::log($user->getErrors());

            craft()->httpSession->add("registered", false);
            craft()->httpSession->add("account", $user);

            return false;
        }); 

    }

}