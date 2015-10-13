<?php

namespace Amostajo\LaravelShopGatewayOmnipay;

/**
 * Gateway that adds Omnipay payments to Laravel Shop.
 *
 * @author Alejandro Mostajo
 * @copyright Amsgames, LLC
 * @license MIT
 * @package Amostajo\LaravelShopGatewayOmnipay
 * @version 1.0.0
 */

use Exception;
use Omnipay\Omnipay;
use Omnipay\Common\CreditCard;
use Amsgames\LaravelShop\Exceptions\CheckoutException;
use Amsgames\LaravelShop\Exceptions\GatewayException;
use Amsgames\LaravelShop\Exceptions\ShopException;
use Amsgames\LaravelShop\Core\PaymentGateway;
use Illuminate\Support\Facades\Config;

class GatewayOmnipay extends PaymentGateway
{
    /**
     * Omnipay object gateway.
     * @var object
     */
    protected $omnipay;

    /**
     * Omnipay credit card.
     * @var object
     */
    protected $creditCard;

    /**
     * Flag that indicates if a credit card should be used or not.
     * @var bool
     */
    public $isCreditCard = false;

    /**
     * Approval URL to redirect to.
     * @var string
     */
    protected $approvalUrl = '';

    /**
     * Additional options for authorize and purchase methods.
     * @var array
     */
    protected $options = array();

    /**
     * Returns paypal url for approval.
     * @since 1.0.0
     *
     * @return string
     */
    public function getApprovalUrl()
    {
        return $this->approvalUrl;
    }

    /**
     * Generic getter.
     * @since 1.0.0
     *
     * @param string $property Property name.
     *
     * @return mixed
     */
    public function __get( $property )
    {
        return property_exists( $this, $property )
            ? $this->$property
            : null;
    }

    /**
     * Creates omnipay with a specific gateway.
     * @since 1.0.0
     *
     * @param string $gatewayName Gateway name to init omnipay.
     */
    public function create($gatewayName)
    {
        $this->omnipay = Omnipay::create( $gatewayName );
    }

    /**
     * Creates omnipay with a specific gateway.
     * @since 1.0.0
     *
     * @param string $gatewayName Gateway name to init omnipay.
     */
    public function setCreditCard(array $data)
    {
        $this->creditCard = new CreditCard($data);
        $this->isCreditCard = true;
    }

    /**
     * Adds an extra option for authorization and purchase processes.
     * @since 1.0.0
     *
     * @param string $key   Option key.
     * @param mixed  $value Option value.
     */
    public function addOption($key, $value)
    {
    	$this->options[$key] = $value;
    }

    /**
     * Called on cart checkout.
     * @since 1.0.0
     *
     * @param Cart $cart Cart.
     */
    public function onCheckout($cart)
    {
        if (!isset($this->omnipay))
            throw new ShopException('Omnipay gateway not set.', 0);

        if ($this->isCreditCard && !isset($this->creditCard))
            throw new GatewayException('Credit Card not set.', 1);
        try {

            $response = $this->omnipay->authorize(array_merge([
                'amount'    => $cart->total,
                'currency'  => Config::get('shop.currency'),
                'card'      => $this->isCreditCard ? $this->creditCard : [],
                'returnUrl' => $this->callbackSuccess,
            ], $this->options))->send();

            if (!$response->isSuccessful()) {
                throw new CheckoutException($response->getMessage(), 1);
            }

        } catch (Exception $e) {

            throw new CheckoutException(
                'Exception caught while attempting authorize.' . "\n" . 
                $e->getMessage(),
                1
            );
        }
    }

    /**
     * Called by shop to charge order's amount.
     * @since 1.0.0
     *
     * @param Cart $cart Cart.
     *
     * @return bool
     */
    public function onCharge($order)
    {
        if (!isset($this->omnipay))
            throw new ShopException('Omnipay gateway not set.', 0);

        try {

            $response = $this->omnipay->purchase(array_merge([
                'amount'    => $order->total,
                'currency'  => Config::get('shop.currency'),
                'card'      => $this->isCreditCard ? $this->creditCard : [],
                'returnUrl' => $this->callbackSuccess,
                'cancelUrl' => $this->callbackFail,
            ], $this->options))->send();

            if ($response->isSuccessful()) {

                $this->transactionId = $response->getTransactionReference();

                $this->detail = 'Success';


            } elseif ($response->isRedirect()) {

                $this->statusCode = 'pending';

                $this->approvalUrl = $response->getRedirectUrl();

                $this->detail = sprintf('Pending approval: %s', $response->getRedirectUrl());

            } else {

                throw new GatewayException($response->getMessage(), 1);

            }

            return true;

        } catch (Exception $e) {

            throw new ShopException(
                $e->getMessage(),
                1000,
                $e
            );

        }

        return false;
    }

    /**
     * Called on callback.
     * @since 1.0.0
     *
     * @param Order $order Order.
     * @param mixed $data  Request input from callback.
     *
     * @return bool
     */
    public function onCallbackSuccess($order, $data = null)
    {
        try {

            $response = $this->omnipay->completePurchase([
                'transactionId'    => $order->transactionId,
            ])->send();

            if ($response->isSuccessful()) {

                $this->statusCode = 'completed';

                $this->transactionId = $this->omnipay->getTransactionId();

                $this->detail = 'Success';

            } else {

                throw new GatewayException($response->getMessage(), 1);

            }

        } catch (\Exception $e) {

            throw new GatewayException(
                $e->getMessage(),
                1000,
                $e
            );

        }
    }
}