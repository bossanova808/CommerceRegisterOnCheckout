<?php
namespace Craft;

class CommerceRegisterOnCheckoutController extends BaseController
{
    protected $allowAnonymous = true;

    public function actionSaveRegistrationDetails()
    {

        //Must be called by POST
        $this->requirePostRequest();
        $url = craft()->request->getUrlReferrer();

        CommerceRegisterOnCheckoutPlugin::log("actionSaveRegistrationDetails (from: $url )");

        $ajax = craft()->request->isAjaxRequest();
        $cart = craft()->commerce_cart->getCart();
        $vars = craft()->request->getPost();

        $password = $vars["password"];
        if(!$password){
            // Password is required (encryption of empty string fails)
            if($ajax){
                $this->returnErrorJson("Password cannot be empty");
            } else {
                throw new HttpException(400, Craft::t("Password cannot be empty"));
            }
        }

        $encryptedPassword = base64_encode(craft()->security->encrypt($password));

        $number = 0;
        $lusaid = 0;
        $lubaid = 0;

        $number = $cart->number;

        if($cart->shippingAddress){
            $lusaid = $cart->shippingAddress->id;
        }
        
        if($cart->billingAddress){
           $lubaid = $cart->billingAddress->id;
        }

        CommerceRegisterOnCheckoutPlugin::log("Saving registration record for order: " . $number ." lusaid " . $lusaid . " lubaid " . $lubaid);
    
        $result = craft()->db->createCommand()->insert("commerceregisteroncheckout",["orderNumber"=>$number, "EPW"=>$encryptedPassword, "lastUsedShippingAddressId"=> $lusaid, "lastUsedBillingAddressId"=>$lubaid ]);

        // Appropriate Ajax responses...
        if($ajax){
            $this->returnJson(["success"=>true]);
        }

        //Re-direct to a results page if a url has been posted and this is not an ajax call
        if($url){
            $this->redirect($url);
        }
    }

}