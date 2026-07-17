<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Http\EndpointResolver;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Math\Random;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class WebhookManagement - Registers Global API webhook subscriptions
 * pointing to the module notification endpoint (getnet/notification/all).
 */
class WebhookManagement
{
    /**
     * Transaction events consumed by Controller/Notification/All.
     *
     * The PIX_* and BOLETO_* events are not in the swagger — the full list was
     * obtained from the API validation error (see SPEC-API-GLOBAL.md, section 5.3).
     */
    public const EVENTS = [
        'PIX_APPROVED_TRANSACTIONS',
        'PIX_DENIED_TRANSACTIONS',
        'PIX_CANCELED_TRANSACTIONS',
        'PIX_ERROR_TRANSACTIONS',
        'BOLETO_PAID_TRANSACTIONS',
        'BOLETO_CANCELED_TRANSACTIONS',
        'BOLETO_DENIED_TRANSACTIONS',
    ];

    /**
     * Events removed from the module that may still have live subscriptions on
     * the gateway and must be deleted during a re-sync.
     */
    public const LEGACY_EVENTS = [
        'APPROVED_TRANSACTIONS',
        'REJECTED_TRANSACTIONS',
        'REFUNDED_TRANSACTIONS',
        'CANCELLED_TRANSACTIONS',
        'CAPTURED_TRANSACTIONS',
        'BOLETO_REGISTERED_TRANSACTIONS',
        'BOLETO_ERROR_TRANSACTIONS',
    ];

    /**
     * Notification route registered as callback.
     */
    public const CALLBACK_ROUTE = 'getnet/notification/all';

    /**
     * @var ApiManagement
     */
    protected $api;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EndpointResolver
     */
    protected $endpointResolver;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Random
     */
    protected $mathRandom;

    /**
     * @param ApiManagement         $api
     * @param Config                $config
     * @param EndpointResolver      $endpointResolver
     * @param StoreManagerInterface $storeManager
     * @param Random                $mathRandom
     */
    public function __construct(
        ApiManagement $api,
        Config $config,
        EndpointResolver $endpointResolver,
        StoreManagerInterface $storeManager,
        Random $mathRandom
    ) {
        $this->api = $api;
        $this->config = $config;
        $this->endpointResolver = $endpointResolver;
        $this->storeManager = $storeManager;
        $this->mathRandom = $mathRandom;
    }

    /**
     * Register the notification callback for all transaction events.
     *
     * @param int|null    $storeId
     * @param string|null $callbackUrl Optional override (e.g. a public tunnel/relay
     *                                 when the store is not reachable by Getnet)
     *
     * @throws LocalizedException
     *
     * @return array Result by event: ['success' => bool, 'message' => string]
     */
    public function register($storeId = null, ?string $callbackUrl = null): array
    {
        if ($this->config->getApiType($storeId) !== Config::API_TYPE_GLOBAL) {
            throw new LocalizedException(
                __('Webhook subscription is available only in the API Global.')
            );
        }

        $store = $this->storeManager->getStore($storeId);

        // Priority: explicit argument > admin config override > store notification URL
        $callbackUrl = $callbackUrl
            ?: $this->config->getAddtionalValue('webhook_callback_url', $storeId)
            ?: $store->getBaseUrl(UrlInterface::URL_TYPE_LINK).self::CALLBACK_ROUTE;

        if (!filter_var($callbackUrl, FILTER_VALIDATE_URL)) {
            throw new LocalizedException(
                __('Invalid callback URL.')
            );
        }
        $sellerId = $this->config->getMerchantGatewaySellerId($storeId);
        $path = $this->endpointResolver->resolve(EndpointResolver::WEBHOOK_SUBSCRIBE, $storeId);

        $results = [];

        foreach (self::EVENTS as $event) {
            $request = [
                'store_id'            => (int) $store->getId(),
                'seller_id'           => $sellerId,
                'callback_url'        => $callbackUrl,
                'event'               => $event,
                'authentication_type' => 'user_credentials',
                'authentication_data' => [
                    // The notification endpoint validates origin by seller_id, not by these credentials
                    'user'     => $sellerId,
                    'password' => $this->mathRandom->getUniqueHash(),
                ],
            ];

            $results[$event] = $this->subscribe($path, $request);
        }

        return $results;
    }

    /**
     * All webhook events the module has ever registered (current + legacy).
     *
     * @return array
     */
    public function getAllKnownEvents(): array
    {
        return array_values(array_unique(array_merge(self::EVENTS, self::LEGACY_EVENTS)));
    }

    /**
     * Delete webhook subscriptions for the given events.
     *
     * @param int|null   $storeId
     * @param array|null $events  Defaults to every known event (current + legacy)
     *
     * @throws LocalizedException
     *
     * @return array Result by event: ['success' => bool, 'message' => string]
     */
    public function unregister($storeId = null, ?array $events = null): array
    {
        if ($this->config->getApiType($storeId) !== Config::API_TYPE_GLOBAL) {
            throw new LocalizedException(
                __('Webhook subscription is available only in the API Global.')
            );
        }

        $events = $events ?: $this->getAllKnownEvents();
        $store = $this->storeManager->getStore($storeId);
        $results = [];

        foreach ($events as $event) {
            $path = $this->endpointResolver->resolve(
                EndpointResolver::WEBHOOK_UNSUBSCRIBE,
                $storeId,
                [$event]
            );

            $results[$event] = $this->deleteSubscription($path, (int) $store->getId());
        }

        return $results;
    }

    /**
     * Re-sync the gateway subscriptions with the module.
     *
     * Deletes every known event (current + legacy) then registers the current ones.
     *
     * @param int|null    $storeId
     * @param string|null $callbackUrl
     *
     * @throws LocalizedException
     *
     * @return array ['unregistered' => array, 'registered' => array]
     */
    public function sync($storeId = null, ?string $callbackUrl = null): array
    {
        return [
            'unregistered' => $this->unregister($storeId, $this->getAllKnownEvents()),
            'registered'   => $this->register($storeId, $callbackUrl),
        ];
    }

    /**
     * Delete a single subscription by event name.
     *
     * @param string $path
     * @param int    $storeId
     *
     * @return array
     */
    public function deleteSubscription(string $path, int $storeId): array
    {
        try {
            $response = $this->api->sendDeleteRequest($path, ['store_id' => $storeId]);
        } catch (\Exception $exc) {
            return [
                'success' => false,
                'message' => $exc->getMessage(),
            ];
        }

        return $this->evaluateDeleteResponse($response);
    }

    /**
     * Evaluate the delete response.
     *
     * A missing subscription is an acceptable outcome for a delete/re-sync (idempotent).
     *
     * @param array $response
     *
     * @return array
     */
    public function evaluateDeleteResponse(array $response): array
    {
        if (($response['success'] ?? false) === true
            || ($response['status'] ?? null) === 'success'
        ) {
            return [
                'success' => true,
                'message' => (string) __('Unsubscribed'),
            ];
        }

        $message = $response['message']
            ?? $response['error_description']
            ?? (string) __('Unexpected gateway response.');

        if (stripos((string) $message, 'not found') !== false
            || stripos((string) $message, 'no subscription') !== false
        ) {
            return [
                'success' => true,
                'message' => (string) __('Already removed'),
            ];
        }

        return [
            'success' => false,
            'message' => (string) $message,
        ];
    }

    /**
     * Subscribe a single event.
     *
     * @param string $path
     * @param array  $request
     *
     * @return array
     */
    public function subscribe(string $path, array $request): array
    {
        try {
            $response = $this->api->sendPostRequest($path, $request);
        } catch (\Exception $exc) {
            return [
                'success' => false,
                'message' => $exc->getMessage(),
            ];
        }

        return $this->evaluateResponse($response);
    }

    /**
     * Evaluate the subscription response.
     *
     * Real 201 body (differs from the swagger): {"status":"success","message":"…",
     * "data":{…,"subscription_id":"…"}}.
     *
     * @param array $response
     *
     * @return array
     */
    public function evaluateResponse(array $response): array
    {
        $subscriptionId = $response['data']['subscription_id'] ?? null;

        if (($response['status'] ?? null) === 'success' || $subscriptionId || isset($response['event'])) {
            $message = (string) __('Subscribed');

            if ($subscriptionId) {
                $message .= ' ('.$subscriptionId.')';
            }

            return [
                'success' => true,
                'message' => $message,
            ];
        }

        $message = $response['message']
            ?? $response['error_description']
            ?? (string) __('Unexpected gateway response.');

        return [
            'success' => false,
            'message' => (string) $message,
        ];
    }
}
