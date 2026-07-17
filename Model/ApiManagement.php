<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Model;

use Getnet\PaymentMagento\Gateway\Http\Api;

/**
 * Class ApiManagement - Facade delegating API calls to the central Gateway HTTP client.
 *
 * Kept for backward compatibility: webhooks, crons and tokenization models depend on it.
 * All oAuth and HTTP handling lives in Gateway\Http\Api.
 */
class ApiManagement
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @param Api $api
     */
    public function __construct(
        Api $api
    ) {
        $this->api = $api;
    }

    /**
     * Get Auth.
     *
     * @param int|null $storeId
     *
     * @return string|null
     */
    public function getAuth($storeId)
    {
        return $this->api->getAuth($storeId);
    }

    /**
     * Send Post Request.
     *
     * @param string      $path
     * @param array       $request
     * @param string|null $additional
     *
     * @return array
     */
    public function sendPostRequest($path, $request, $additional = null)
    {
        return $this->api->sendPostRequest(null, $path, $request, $additional);
    }

    /**
     * Send Get By Param.
     *
     * @param string $path
     * @param array  $request
     *
     * @return array
     */
    public function sendGetByParam($path, $request)
    {
        return $this->api->sendGetByParam(null, $path, $request);
    }

    /**
     * Send Get Request.
     *
     * @param string $path
     * @param array  $request
     *
     * @return array
     */
    public function sendGetRequest($path, $request)
    {
        return $this->api->sendGetRequest(null, $path, $request);
    }

    /**
     * Send Delete Request.
     *
     * @param string $path
     * @param array  $request
     *
     * @return array
     */
    public function sendDeleteRequest($path, $request)
    {
        return $this->api->sendDeleteRequest(null, $path, $request);
    }
}
