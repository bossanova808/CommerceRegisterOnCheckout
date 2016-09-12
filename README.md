# Commerce Register on Checkout plugin for Craft CMS

Register customers on checkout with Craft Commerce

## Installation

To install Commerce Register on Checkout, follow these steps:

1. Downloaded the latest release
2. Unzip and copy the plugin folder `commerceregisteroncheckout` to your `craft/plugins` folder
3. Install plugin in the Craft Control Panel under Settings > Plugins
4. N.B. The plugin folder should be named `commerceregisteroncheckout` for Craft to see it.  

Commerce Register on Checkout has been tested with Craft 2.6+ and Commerce 1.1+, and is in daily use on working, live stores.

## Commerce Register on Checkout Overview

This plugin allows you to add user registration as part of your Commerce checkout process.  

Currently only allows for regsitering users with their username set to their email - however given commerce keys everything by the email, this is the most natural set up anyway.

## Configuring Commerce Register on Checkout

You can turn on some extra logging in the plugin settings - it's probably fine to leave this on even on your live server - it just logs all succesful user registrations in addition to any errors/warnings.

## Using Commerce Register on Checkout

This plugin listens to the `commerce_orders.onOrderComplete` event (rather than the `onBeforeOrderComplete` event - which might cause issues/confusion if the order payment fails but the user was registered - also, we don't want anything getting in the way of taking orders!).

You will need to add two input variables to the form you use to post to the `commerce/payments/pay` controller (i.e. your payment form).  

These two inputs are:

* `registerUser` to trigger the user registration process
* `password` which holds the user's new password 

(You should validate the password on the front end to make sure it meets Craft's minimum 6 character requirement).

Example:

    <input type="checkbox" value="true" id="registerUser" checked>
    <input type="password" value="" placeholder="New Password (min. 6 characters)" name="password">

By default, this plugin looks at the current order's billing address for the first and last name data.  You can change that behaviour by instead explicitly passing this data in two more input variables:

* `firstName`
* `lastName`

Commerce Register on Checkout will, if all goes well, now register the user once the order is complete.  It will also immediately log them in, and assign  them to the default user group, just like a normal user registration.

## Handling Success & Errors

In your order complete template, you can check the results of the registration process and handle any errors - giving the user another chance to register if something went wrong, for example.  The users account information is returned to the template in an `account` variable.

Here's some sketch code to get you started:

```

    {# Get the results of user registration, if there are any... #}
    {% set registered = craft.commerceRegisterOnCheckout.checkoutRegistration().registered ?? null %}
    {% set account = craft.commerceRegisterOnCheckout.checkoutRegistration().account ?? null %}

    {# Was registration attempted? #}
    {% if registered|length %}

        {# Success, if true #}
        {% if craft.commerceRegisterOnCheckout.checkoutRegistration().registered %}
            <do some stuff>
        
        {# Failure, otherwise #}
        {% else %}
            {% if account|length %}
                {% if "has already been taken" in account.getError('email') %}

                <etc, e.g. present a user registration form with as much filled in as possible>
```


## Commerce Register on Checkout Changelog

### 0.0.1 -- 2016.09.06

* Initial release

Brought to you by [Jeremy Daalder](https://github.com/bossanova808)

## Issues?

Please use github issues or find me on the Craft slack, I mostly hang out in the #commerce channel

## Icon

by IconfactoryTeam from the Noun Project
