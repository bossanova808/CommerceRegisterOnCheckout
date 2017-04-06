# Commerce Register on Checkout plugin for Craft CMS

Register customers on checkout with Craft Commerce

## Installation

To install Commerce Register on Checkout (CROC), follow these steps:

1. Downloaded the latest release
2. Unzip and copy the plugin folder `commerceregisteroncheckout` to your `craft/plugins` folder
3. Install plugin in the Craft Control Panel under Settings > Plugins
4. N.B. The plugin folder should be named `commerceregisteroncheckout` for Craft to see it.  

Commerce Register on Checkout has been tested with Craft 2.6+ and Commerce 1.1+, and is in daily use on working, live stores.

## Commerce Register on Checkout Overview

This plugin allows you to more easily add user registration as part of your Commerce checkout process.  

Currently only allows for registering users with their username set to their email - however given commerce keys everything by the email, this is the most natural set up anyway.

As of 0.0.6+ it also transfers address records and sets the last used billing and shipping address IDs so that the newly created Craft Users immediately get access to their address records in their address book for their next order (this does not occur if you integrate a standard Craft registration form).

## Why Not Use A Standard Craft Registation Form?

An easy solution to this issue is to present a mostly pre-filled form to the user immediately following checkout - and of course this works fine and means one less plugin, which is a good thing.  However, from a business perspective and based on real world experience, you will see *vastly* lower user registration numbers with this method.

See discussion here: https://craftcms.stackexchange.com/questions/18974/register-a-user-as-part-of-a-commerce-checkout/18993#18993

In short, this plugin  allows for a more integrated approach - registering the user _during_ the actual checkout, which signifcantly increases the number of users that will register, and this has many potential business benefits of course.

In addition, if you use a standard registration form, your customers will need to re-enter their address details when they do their second order as these are not automatically transferred with the registration (order records are, but not addresses). This is less than ideal from a UX perspective.

## Configuring Commerce Register on Checkout

You can turn on some extra logging in the plugin settings - it's probably fine to leave this on even on your live server - it just logs all successful user registrations in addition to any errors/warnings.

## Using Commerce Register on Checkout

At any point in your checkout flow before the final payment/order completion, you need to make one additional POST request.  I do this by ajax just before the payment form is submitted.

This request must post the users desired password to the `saveRegistrationDetails` controller.  This password is then encrypted using Craft's built in encryption mechanisms, and saved along with the order number to a temporary database record.

It's very simple, here's some sample code:

HTML Form:

    <input type="checkbox" value="true" id="registerUser" name="registerUser" checked>
    <input type="password" value="" placeholder="New Password (min. 6 characters)" name="password">

JS:

Somehere in e.g. your master layout set a variable to the CSRF token value (if you're using CSRF verification):

        window.csrfTokenValue = "{{ craft.request.csrfToken|e('js') }}"

Then the JS you need is just:

        if ($('#registerUser').prop('checked')) {
            var pw_value = $('input[type="password"]').val();
            var pw_error = '';
            if (pw_value.length < 6) {
                pw_error = "Password length must be 6 or more characters";
            }
            if (pw_error) {
                alert(pw_error);
            }
            else {
                $.ajax({
                    type: 'POST',
                    url: '/actions/commerceRegisterOnCheckout/saveRegistrationDetails',
                    data: {
                        CRAFT_CSRF_TOKEN: window.csrfTokenValue,
                        password: pw_value,
                    }
                });
            }
        }

(NB - as above you should also validate the password on the front end to make sure it meets Craft's minimum 6 character requirement, or the user registration later may fail).

The plugin then listens to the `commerce_orders.onOrderComplete` event.  For each completed order it looks for a saved record, and if it finds one then registers the user.  It will also immediately log them in, and assign them to the default user group, just like a normal user registration.

## Handling Success & Errors

In your order complete template, you can check the results of the registration process and handle any errors - giving the user another chance to register if something went wrong, for example.  The users account information is returned to the template in an `account` variable.

Here's some sketch code to get you started:

```

    {# Get the results of user registration, if there are any... #}
    {% set registered = craft.commerceRegisterOnCheckout.checkoutRegistered ?? null %}
    {% set account = craft.commerceRegisterOnCheckout.checkoutAccount ?? null %}


    {# Was registration attempted? #}
    {% if registered|length %}

        {# Success, if true #}
        {% if craft.commerceRegisterOnCheckout.checkoutRegistration().registered %}
            <do some stuff>
        
        {# Failure, otherwise #}
        {% else %}
            {% if account|length %}
                {% if "has already been taken" in account.getError('email') %}

                ... Point ou they are already registered...
                
            {% else %}
                ...etc, e.g. present a user registration form with as much filled in as possible>
            }
```


## Events

CROC offers an event on the completion of succesful user registration `onRegisterComplete`.

The event parameters are the Order and User models.

You can listen and act on this event if needed, e.g.:

```

        craft()->on('commerceregisteroncheckout.onRegisterComplete', function($event){

            $order = $event->params['order'];
            $user = $event->params['user'];

            ...etc

        }

```


## Commerce Register on Checkout Changelog

See [releases.json](https://github.com/bossanova808/CommerceRegisterOnCheckout/blob/master/releases.json)

## Who is to blame?

Brought to you by [Jeremy Daalder](https://github.com/bossanova808)

## Issues?

Please use github issues or find me on the Craft slack, I mostly hang out in the #commerce channel

## Icon

by IconfactoryTeam from the Noun Project
