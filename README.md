# OMNIPAY GATEWAY (for Laravel Shop Package)
--------------------------------

[![Latest Stable Version](https://poser.pugx.org/amostajo/laravel-shop-gateway-omnipay/v/stable)](https://packagist.org/packages/amostajo/laravel-shop-gateway-omnipay)
[![Total Downloads](https://poser.pugx.org/amostajo/laravel-shop-gateway-omnipay/downloads)](https://packagist.org/packages/amostajo/laravel-shop-gateway-omnipay)
[![License](https://poser.pugx.org/amostajo/laravel-shop-gateway-omnipay/license)](https://packagist.org/packages/amostajo/laravel-shop-gateway-omnipay)

Omnipay Gateway solution for [Laravel Shop](https://github.com/amsgames/laravel-shop).

Enables multiple gateway payment, like PayPal, 2Checkout, Stripe and others.
See the full list at [Omnipay's page](https://github.com/thephpleague/omnipay).

## Gateways

This package comes with:

* Omnipay

## Contents

- [Installation](#installation)
- [Adding a service](#adding-a-service)
- [Gateway Usage](#gateway-usage)
    - [Accessing Omnipay](#accessing-omnipay)
    - [Adding Options](#adding-options)
    - [Callbacks](#callbacks)
- [Tested Services](#tested-services)
- [License](#license)
- [Additional Information](#aditional-information)

## Installation

Add

```json
"amostajo/laravel-shop-gateway-omnipay": "1.0.*"
```

to your `composer.json`. Then run `composer install` or `composer update`.

Then in your `config/shop.php` add 

```php
'omnipay'           =>  Amostajo\LaravelShopGatewayOmnipay\GatewayOmnipay::class,
```
    
in the `gateways` array.

## Adding a service

Once installed, the next step will be to add the service of your choice in `composer.json` file.

The services available are listed here:

[https://github.com/thephpleague/omnipay#payment-gateways](https://github.com/thephpleague/omnipay#payment-gateways)

For example, the following dependency must be added to use **Stripe**:

```json
"omnipay/stripe": "~2.0"
```

## Gateway Usage

The following example will give you an idea of how use and access omnipay:

```php
// (1) - Set gateway
Shop::setGateway('omnipay');

// (2) - Indicate service to use
Shop::gateway()->create('PayPal_Rest');

// (3) - Initialize your service (varies from service)
Shop::gateway()->omnipay->initialize([
	'clientId' => '...',
	'secret'   => '...',
	'testMode' => true,
]);

// (4) - Add credit card for validation (optional depending service)
Shop::gateway()->setCreditCard([
	'number' 			=> '4111111111111111',
	'expiryMonth'		=> '1',
	'expiryYear'		=> '2019',
	'cvv'				=> '123',
	'firstName'			=> 'John',
	'lastName'			=> 'Doe',
	'billingAddress1'	=> '666 grand canyon',
	'billingCountry'	=> 'US',
	'billingCity'		=> 'TX',
	'billingPostcode'	=> '12345',
	'billingState'		=> 'TX',
]);

// (5) - Call checkout
if (!Shop::checkout()) {
  echo Shop::exception()->getMessage(); // echos: card validation error.
}

// (6) - Create order
$order = Shop::placeOrder();

// (7) - Review payment
if ($order->hasFailed) {

  echo Shop::exception()->getMessage(); // echos: payment error.

}
```

The lines may vary depending on the service chosen.

*NOTE:* Checkout and placing order shouldn't vary from standard Laravel Shop flow.

### Accessing Omnipay

You can always access the `omnipay` object if you need to set or call any specific method required by a service:

```php
// (1) - Set gateway
Shop::setGateway('omnipay');
Shop::gateway()->create('Stripe');

// (2) - Setting method / calling specific method
Shop::gateway()->omnipay->setSpecific();
```

### Adding Options

You can add more options, apart from `amount`, `currency` and `card`, to the authorization and purchase methods:

```php
// (1) - Set gateway
Shop::setGateway('omnipay');
Shop::gateway()->create('Stripe');

// (2) - Adding an option
Shop::gateway()->addOption('token', $stripetoken);

// (3) - Any operation that follows
Shop::checkout();
```

### Callbacks

Use the following example when callbacks are needed:

```php
// (1) - Set gateway
Shop::setGateway('omnipay');
Shop::gateway()->create('PayPal_Express');

// (2) - Authentication
Shop::gateway()->omnipay->setUsername('...');
Shop::gateway()->omnipay->setPassword('...');

// (2) - Call checkout / OPTIONAL
Shop::checkout();

// (3) - Create order
$order = Shop::placeOrder();

// (4) - Review order and redirect to payment
if ($order->isPending) {

  // PayPal URL to redirect to proceed with payment
  $approvalUrl = Shop::gateway()->getApprovalUrl();

  // Redirect to url
  return redirect($approvalUrl);
}

// (5) - Callback
// You don't have to do anything.
// Laravel Shop will handle the callback and redirect the customer to the configured route.
```

### Tested Services

* PayPal
* Stripe

## License

This package is free software distributed under the terms of the MIT license.

## Additional Information

This package uses [Omnipay](https://github.com/thephpleague/omnipay).