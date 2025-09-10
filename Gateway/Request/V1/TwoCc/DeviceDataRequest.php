<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\V1\TwoCc;

use Getnet\PaymentMagento\Gateway\Config\Config as ConfigBase;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Framework\HTTP\Header as HeaderClient;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Session\SessionManager;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Ramsey\Uuid\Uuid;

/**
 * Class Device Data Request - User Device Data Structure.
 */
class DeviceDataRequest implements BuilderInterface
{
    /**
     * Device data customer.
     */
    public const DEVICE_DATA = 'device';

    /**
     * Remote IP data.
     */
    public const REMOTE_IP = 'ip_address';

    /**
     * Remote User Agent data.
     */
    public const REMOTE_USER_AGENT = 'userAgent';

    /**
     * Device Id data.
     */
    public const DEVICE_ID = 'device_id';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var remoteAddress
     */
    protected $remoteAddress;

    /**
     * @var headerClient
     */
    protected $headerClient;

    /**
     * @var SessionManager
     */
    protected $session;

    /**
     * @var ConfigBase
     */
    protected $configBase;

    /**
     * @param RemoteAddress  $remoteAddress
     * @param HeaderClient   $headerClient
     * @param SubjectReader  $subjectReader
     * @param SessionManager $session
     * @param ConfigBase     $configBase
     */
    public function __construct(
        RemoteAddress $remoteAddress,
        HeaderClient $headerClient,
        SubjectReader $subjectReader,
        SessionManager $session,
        ConfigBase $configBase
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->headerClient = $headerClient;
        $this->subjectReader = $subjectReader;
        $this->session = $session;
        $this->configBase = $configBase;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment'])
        || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();
        $order = $payment->getOrder();
        $storeId = $order->getStoreId();

        $result = [];
        $ipCustomer = $this->remoteAddress->getRemoteAddress();
        if (empty($ipCustomer)) {
            $ipCustomer = $order->getXForwardedFor();
        }

        $deviceId = $this->configBase->getMerchantGatewaySellerId($storeId) . '-' . $this->generateFingerPrintId();

        $result[self::DEVICE_DATA] = [
            self::REMOTE_IP         => $ipCustomer,
            // self::REMOTE_USER_AGENT => $this->headerClient->getHttpUserAgent(),
            self::DEVICE_ID         => $deviceId,
        ];

        $paymentInfo = $paymentDO->getPayment();

        $paymentInfo->setAdditionalInformation(
            self::DEVICE_DATA,
            $result[self::DEVICE_DATA]
        );

        return $result;
    }

    /**
     * Generate UUID v5 from session ID.
     *
     * @return string
     */
    private function generateFingerPrintId(): string
    {
        try {
            $sessionId = $this->session->getSessionId();
            $namespace = Uuid::NAMESPACE_DNS;
            $uuid = Uuid::uuid5($namespace, $sessionId);
            return $uuid->toString();
        } catch (\Exception $e) {
            // Fallback to UUID v4 if generation fails
            return Uuid::uuid4()->toString();
        }
    }
}
