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
        return '0.0.1';
    }

    /**
     * @return string
     */
    public function getSchemaVersion()
    {
        return '0.0.0';
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
    }

    /**
     */
    public function onAfterUninstall()
    {
    }

    /**
     * @return mixed
     */
    public function init(){

        self::$settings = $this->getSettings();

        // Listen to onOrderComplete as we definitely don't want to make submitting orders have more potential issues...
        craft()->on('commerce_orders.onOrderComplete', function($event){

            $order = $event->params['order'];

            // Registers users only if the commerce/payments/pay controller was called with a parameter 'registerUser'      
            if(craft()->request->getParam('registerUser')){

                $password = craft()->request->getParam('password');
                
                $firstName = "";
                $lastName = "";     

                //Is there a billing address?  If so by default use that
                $address = $order->getBillingAddress();
                if($address){
                    $firstName = $address->firstName;
                    $lastName = $address->lastName;
                }

                //Overrule with POST data if that's supplied instead
                if(craft()->request->getParam('firstName')){
                    $firstName = craft()->request->getParam('firstName');
                }
                if(craft()->request->getParam('lastName')){
                    $lastName = craft()->request->getParam('lastName');
                }                

                //@TODO - Support other data/custom fields etc??

                //@TODO - we offer only username = email support currently - since in Commerce everything is keyed by emails...
                $user = new UserModel();
                $user->username         = $order->email;
                $user->email            = $order->email;
                $user->firstName        = $firstName;
                $user->lastName         = $lastName;
                $user->newPassword      = $password;
     
                $success = craft()->users->saveUser($user);

                if ($success) {

                    CommerceRegisterOnCheckoutPlugin::log("Registered new user $address->firstName $address->lastName [$order->email] on checkout");

                    // Assign them to the default user group (customers)
                    craft()->userGroups->assignUserToDefaultGroup($user);
                    // & Log them in
                    craft()->userSession->loginByUserId($user->id);
                    // & record we've done this so the template variable can be set
                    craft()->httpSession->add("registered", true);

                    return true;
                }
                else {

                    CommerceRegisterOnCheckoutPlugin::logError("Failed to register new user $address->firstName $address->lastName [$order->email] on checkout");
                    CommerceRegisterOnCheckoutPlugin::log($user->getErrors());

                    craft()->httpSession->add("registered", false);
                    craft()->httpSession->add("account", $user);

                    return false;
                }
            }


        }); 

    }


 

  



}