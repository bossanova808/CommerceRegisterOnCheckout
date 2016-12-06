<?php
namespace Craft;

class CommerceRegisterOnCheckoutController extends BaseController
{
    protected $allowAnonymous = true;

    public function actionSaveRegistrationDetails()
    {

        //Must be called by POST
        $this->requirePostRequest();

        $ajax = craft()->request->isAjaxRequest();
        $cart = craft()->commerce_cart->getCart();
        $vars = craft()->request->getPost();

        $password = $vars['password'];
        $encryptedPassword = base64_encode(craft()->security->encrypt($password));

        CommerceRegisterOnCheckoutPlugin::log("Saving registration record for order: " . $cart->number);
    
        $result = craft()->db->createCommand()->insert("commerceregisteroncheckout",["orderNumber"=>$cart->number, "EPW"=>$encryptedPassword]);

        // Appropriate Ajax responses...
        if($ajax){
            $this->returnJson(["success"=>true]);
        }

    }

}