<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Http\Client\ApplePay;

use Getnet\PaymentMagento\Gateway\Config\ConfigApplePay;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\UrlInterface;

/**
 * Class MerchantValidationClient - Apple Pay merchant validation (mutual TLS with the Merchant Identity Certificate).
 *
 * The certificate and the key are kept encrypted in the database and handed to cURL as in-memory blobs
 * (no file is written to the disk). The merchant session returned by Apple is opaque and single use.
 */
class MerchantValidationClient
{
    /**
     * Only Apple hosts are accepted as validation URL (avoids SSRF through the public endpoint).
     */
    private const ALLOWED_HOST_PATTERN = '/(^|\.)apple\.com$/i';

    /**
     * @var ConfigApplePay
     */
    private $config;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param ConfigApplePay $config
     * @param CurlFactory    $curlFactory
     * @param Json           $json
     * @param UrlInterface   $urlBuilder
     */
    public function __construct(
        ConfigApplePay $config,
        CurlFactory $curlFactory,
        Json $json,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->curlFactory = $curlFactory;
        $this->json = $json;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Ask Apple for a merchant session.
     *
     * @param string   $validationUrl
     * @param int|null $storeId
     *
     * @throws LocalizedException
     *
     * @return array
     */
    public function validate(string $validationUrl, $storeId = null): array
    {
        $this->assertValidationUrl($validationUrl);

        $certificate = $this->config->getCertificate($storeId);
        $key = $this->config->getCertificateKey($storeId);
        $merchantId = $this->config->getMerchantIdentifier($storeId);

        if ($certificate === '' || $key === '' || $merchantId === '') {
            throw new LocalizedException(__('Apple Pay is not configured.'));
        }

        $curl = $this->curlFactory->create();
        $curl->setTimeout(15);
        $curl->addHeader('Content-Type', 'application/json');
        $curl->setOption(CURLOPT_SSL_VERIFYPEER, true);
        $curl->setOption(CURLOPT_SSL_VERIFYHOST, 2);
        $curl->setOption(CURLOPT_SSLCERT_BLOB, $certificate);
        $curl->setOption(CURLOPT_SSLKEY_BLOB, $key);

        $password = $this->config->getCertificateKeyPassword($storeId);
        if ($password !== '') {
            $curl->setOption(CURLOPT_KEYPASSWD, $password);
        }

        $curl->post($validationUrl, $this->json->serialize([
            'merchantIdentifier' => $merchantId,
            'displayName'        => $this->config->getDisplayName($storeId),
            'initiative'         => 'web',
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            'initiativeContext'  => (string) parse_url($this->urlBuilder->getBaseUrl(), PHP_URL_HOST),
        ]));

        if ($curl->getStatus() !== 200) {
            throw new LocalizedException(__('Could not validate the Apple Pay merchant.'));
        }

        $session = $this->json->unserialize($curl->getBody());

        if (!is_array($session) || empty($session)) {
            throw new LocalizedException(__('Could not validate the Apple Pay merchant.'));
        }

        return $session;
    }

    /**
     * Only https URLs of Apple hosts on the default port are allowed.
     *
     * @param string $validationUrl
     *
     * @throws LocalizedException
     *
     * @return void
     */
    private function assertValidationUrl(string $validationUrl): void
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $parts = parse_url($validationUrl);

        if (!is_array($parts)
            || ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user'])
            || isset($parts['port'])
            || !preg_match(self::ALLOWED_HOST_PATTERN, (string) ($parts['host'] ?? ''))
        ) {
            throw new LocalizedException(__('Invalid Apple Pay validation URL.'));
        }
    }
}
