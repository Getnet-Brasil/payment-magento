<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http;

use Getnet\PaymentMagento\Gateway\Config\Config;
use InvalidArgumentException;

/**
 * Class EndpointResolver - Maps logical API operations to endpoint paths per api type.
 *
 * Central place for the V2 × Global endpoint switch: clients resolve a logical
 * operation instead of hardcoding paths. Operations without a Global entry are
 * V2-only and always resolve to the V2 path.
 */
class EndpointResolver
{
    /**
     * @const string
     */
    public const CARD_AUTHORIZE = 'card_authorize';

    /**
     * @const string
     */
    public const CARD_CAPTURE = 'card_capture';

    /**
     * @const string
     */
    public const CARD_CANCEL = 'card_cancel';

    /**
     * @const string
     */
    public const PIX_CREATE = 'pix_create';

    /**
     * @const string
     */
    public const BOLETO_CREATE = 'boleto_create';

    /**
     * @const string
     */
    public const TWO_CC_CREATE = 'two_cc_create';

    /**
     * @const string
     */
    public const TWO_CC_CONFIRM = 'two_cc_confirm';

    /**
     * @const string
     */
    public const TWO_CC_CANCEL = 'two_cc_cancel';

    /**
     * @const string
     */
    public const TWO_CC_CANCEL_REQUEST = 'two_cc_cancel_request';

    /**
     * @const string
     */
    public const WALLET_CREATE = 'wallet_create';

    /**
     * @const string
     */
    public const WALLET_GET = 'wallet_get';

    /**
     * @const string
     */
    public const GETPAY_CREATE = 'getpay_create';

    /**
     * @const string
     */
    public const GETPAY_GET = 'getpay_get';

    /**
     * @const string
     */
    public const TOKEN_CARD_CREATE = 'token_card_create';

    /**
     * @const string
     */
    public const VAULT_CARD_CREATE = 'vault_card_create';

    /**
     * @const string
     */
    public const VAULT_CARD_GET = 'vault_card_get';

    /**
     * @const string
     */
    public const REFUND_CONSULT = 'refund_consult';

    /**
     * @const string
     */
    public const WEBHOOK_SUBSCRIBE = 'webhook_subscribe';

    /**
     * @const string
     */
    public const WEBHOOK_UNSUBSCRIBE = 'webhook_unsubscribe';

    /**
     * Endpoint paths by operation and api type.
     *
     * Operations without an API_TYPE_GLOBAL entry are V2-only products
     * (TwoCc/Wallet/GetPay/refund consult) until their Global phase lands.
     */
    private const ENDPOINTS = [
        self::CARD_AUTHORIZE => [
            Config::API_TYPE_V2     => 'v2/payments',
            Config::API_TYPE_GLOBAL => 'dpm/payments-gwproxy/v2/payments',
        ],
        self::CARD_CAPTURE => [
            Config::API_TYPE_V2     => 'v2/payments/capture',
            Config::API_TYPE_GLOBAL => 'dpm/payments-gwproxy/v2/payments/capture',
        ],
        self::CARD_CANCEL => [
            Config::API_TYPE_V2     => 'v2/payments/cancel',
            Config::API_TYPE_GLOBAL => 'dpm/payments-gwproxy/v2/payments/cancel',
        ],
        self::PIX_CREATE => [
            Config::API_TYPE_V2     => 'v2/payments/qrcode/pix',
            Config::API_TYPE_GLOBAL => 'dpm/payments-gwproxy/v2/payments/qrcode/pix',
        ],
        self::BOLETO_CREATE => [
            Config::API_TYPE_V2     => 'v2/payments/boleto',
            Config::API_TYPE_GLOBAL => 'dpm/payments-gwproxy/v2/payments/boleto',
        ],
        self::TWO_CC_CREATE => [
            Config::API_TYPE_V2     => 'v1/payments/combined',
            Config::API_TYPE_GLOBAL => 'dpm/payments-gwproxy/v2/payments/combined',
        ],
        self::TWO_CC_CONFIRM => [
            Config::API_TYPE_V2     => 'v1/payments/combined/confirm',
            Config::API_TYPE_GLOBAL => 'dpm/payments-gwproxy/v2/payments/combined/capture',
        ],
        self::TWO_CC_CANCEL => [
            Config::API_TYPE_V2     => 'v1/payments/combined/cancel',
            Config::API_TYPE_GLOBAL => 'dpm/payments-gwproxy/v2/payments/combined/cancel',
        ],
        self::TWO_CC_CANCEL_REQUEST => [
            Config::API_TYPE_V2     => 'v1/payments/combined/cancel/request',
            // The Global API has no post-settlement request flow: cancel handles both
            Config::API_TYPE_GLOBAL => 'dpm/payments-gwproxy/v2/payments/combined/cancel',
        ],
        self::WALLET_CREATE => [
            Config::API_TYPE_V2 => 'v1/payments/qrcode',
        ],
        self::WALLET_GET => [
            Config::API_TYPE_V2 => 'v1/payments/qrcode/%s',
        ],
        self::GETPAY_CREATE => [
            Config::API_TYPE_V2 => 'v1/payment-links',
        ],
        self::GETPAY_GET => [
            Config::API_TYPE_V2 => 'v1/payment-links/%s',
        ],
        self::TOKEN_CARD_CREATE => [
            Config::API_TYPE_V2     => 'v1/tokens/card',
            Config::API_TYPE_GLOBAL => 'dpm/cofre-gw-proxy/v1/tokens/card',
        ],
        self::VAULT_CARD_CREATE => [
            Config::API_TYPE_V2     => 'v1/cards',
            Config::API_TYPE_GLOBAL => 'dpm/cofre-gw-proxy/v1/cards',
        ],
        self::VAULT_CARD_GET => [
            Config::API_TYPE_V2     => 'v1/cards/%s',
            Config::API_TYPE_GLOBAL => 'dpm/cofre-gw-proxy/v1/cards/%s',
        ],
        self::REFUND_CONSULT => [
            Config::API_TYPE_V2 => 'v1/payments/cancel/request',
        ],
        self::WEBHOOK_SUBSCRIBE => [
            // The swagger documents webhooks/v1/* without the dpm/ prefix, but the
            // deployed API only responds under dpm/ (confirmed on api.pre)
            Config::API_TYPE_GLOBAL => 'dpm/webhooks/v1/subscriptions',
        ],
        self::WEBHOOK_UNSUBSCRIBE => [
            // DELETE is per event name (not by subscription_id) — see SPEC-API-GLOBAL.md
            Config::API_TYPE_GLOBAL => 'dpm/webhooks/v1/subscriptions/%s',
        ],
    ];

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Resolve the endpoint path for a logical operation.
     *
     * @param string   $operation
     * @param int|null $storeId
     * @param array    $params    Positional params for path placeholders (%s)
     *
     * @return string
     */
    public function resolve(string $operation, $storeId = null, array $params = []): string
    {
        if (!isset(self::ENDPOINTS[$operation])) {
            throw new InvalidArgumentException(
                sprintf('Unknown Getnet API operation "%s".', $operation)
            );
        }

        $paths = self::ENDPOINTS[$operation];
        $apiType = $this->config->getApiType($storeId);
        $path = $paths[$apiType] ?? $paths[Config::API_TYPE_V2] ?? reset($paths);

        return $params ? vsprintf($path, $params) : $path;
    }

    /**
     * Whether the operation has a native endpoint in the configured api type.
     *
     * @param string   $operation
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isAvailable(string $operation, $storeId = null): bool
    {
        if (!isset(self::ENDPOINTS[$operation])) {
            return false;
        }

        return isset(self::ENDPOINTS[$operation][$this->config->getApiType($storeId)]);
    }
}
