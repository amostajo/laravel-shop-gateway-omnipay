<?php

use Log;
use App;
use Shop;
use Config;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class OmnipayTest extends TestCase
{
	/**
	 * User set for tests.
	 */
	protected $user;

	/**
	 * Cart set for tests.
	 */
	protected $cart;

	/**
	 * Setups test data.
	 */
	public function setUp()
	{
		parent::setUp();

		$this->user = factory('App\User')->create(['password' => Hash::make('laravel-shop')]);

		Auth::attempt(['email' => $this->user->email, 'password' => 'laravel-shop']);

		$this->cart = App\Cart::current()->add([
			'price' 			=> 9.99,
			'sku'				=> str_random(15),
		]);
	}

	/**
	 * Removes test data.
	 */
	public function tearDown() 
	{
		$this->user->delete();

		parent::tearDown();
	}

	/**
	 * Tests if gateway is integrated with shop.
	 */
	public function testGatewayIntegration()
	{
		Shop::setGateway('omnipay');

		$this->assertEquals(Shop::getGateway(), 'omnipay');

		Shop::gateway()->addOption('test', str_random(15));

		$gateway = Shop::gateway();

		$this->assertNotNull($gateway);

		$this->assertNotEmpty($gateway->toJson());

		$this->assertNotEmpty(Shop::gateway()->options['test']);
	}

	/**
	 * Tests PayPal.
	 */
	public function testPayPalRest()
	{
		Shop::setGateway('omnipay');

		Shop::gateway()->create('PayPal_Rest');

		Shop::gateway()->omnipay->initialize([
			'clientId' => Config::get('services.paypal.client_id'),
			'secret'   => Config::get('services.paypal.secret'),
			'testMode' => Config::get('services.paypal.sandbox'),
		]);

		Shop::gateway()->setCreditCard([
			'number' 			=> Config::get('testing.paypal.creditcard.number'),
			'expiryMonth'		=> Config::get('testing.paypal.creditcard.month'),
			'expiryYear'		=> Config::get('testing.paypal.creditcard.year'),
			'cvv'				=> Config::get('testing.paypal.creditcard.cvv'),
			'firstName'			=> Config::get('testing.paypal.creditcard.firstname'),
			'lastName'			=> Config::get('testing.paypal.creditcard.lastname'),
			'billingAddress1'	=> Config::get('testing.paypal.creditcard.address'),
			'billingCountry'	=> Config::get('testing.paypal.creditcard.country'),
			'billingCity'		=> Config::get('testing.paypal.creditcard.city'),
			'billingPostcode'	=> Config::get('testing.paypal.creditcard.zipcode'),
			'billingState'		=> Config::get('testing.paypal.creditcard.state'),
		]);

		$this->assertTrue(Shop::checkout());

		$order = Shop::placeOrder();

		$this->assertTrue($order->isCompleted);
	}

	/**
	 * Tests PayPal express.
	 */
	public function testPayPalExpress()
	{
		Shop::setGateway('omnipay');

		Shop::gateway()->create('PayPal_Express');

		Shop::gateway()->omnipay->setUsername(Config::get('services.paypal.username'));
		Shop::gateway()->omnipay->setPassword(Config::get('services.paypal.password'));

		Shop::checkout();

		$order = Shop::placeOrder();

		$this->assertTrue($order->hasFailed);
	}

	/**
	 * Tests Stripe.
	 */
	public function testStripe()
	{
		Shop::setGateway('omnipay');

		Shop::gateway()->create('Stripe');

		Shop::gateway()->omnipay->initialize([
			'apiKey' => Config::get('services.stripe.secret'),
		]);

		Shop::gateway()->setCreditCard([
			'number' 			=> Config::get('testing.stripe.creditcard.number'),
			'expiryMonth'		=> Config::get('testing.stripe.creditcard.month'),
			'expiryYear'		=> Config::get('testing.stripe.creditcard.year'),
			'cvv'				=> Config::get('testing.stripe.creditcard.cvv'),
			'firstName'			=> Config::get('testing.stripe.creditcard.firstname'),
			'lastName'			=> Config::get('testing.stripe.creditcard.lastname'),
			'billingAddress1'	=> Config::get('testing.stripe.creditcard.address'),
			'billingCountry'	=> Config::get('testing.stripe.creditcard.country'),
			'billingCity'		=> Config::get('testing.stripe.creditcard.city'),
			'billingPostcode'	=> Config::get('testing.stripe.creditcard.zipcode'),
			'billingState'		=> Config::get('testing.stripe.creditcard.state'),
		]);

		$this->assertTrue(Shop::checkout());

		$order = Shop::placeOrder();

		$this->assertTrue($order->isCompleted);
	}
}