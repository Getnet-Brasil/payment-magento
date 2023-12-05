<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Data\Additional\Device;

use Getnet\PaymentMagento\Gateway\Request\Card\CardInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\Request\Card\Data\Additional\AdditionalInitSchemaDataRequest;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Framework\HTTP\Header as HeaderClient;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Session\SessionManager;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

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
     * @param RemoteAddress  $remoteAddress
     * @param HeaderClient   $headerClient
     * @param SubjectReader  $subjectReader
     * @param SessionManager $session
     */
    public function __construct(
        RemoteAddress $remoteAddress,
        HeaderClient $headerClient,
        SubjectReader $subjectReader,
        SessionManager $session
    ) {
        $this->remoteAddress = $remoteAddress;
        $this->headerClient = $headerClient;
        $this->subjectReader = $subjectReader;
        $this->session = $session;
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

        $result = [];
        $ipCustomer = $this->remoteAddress->getRemoteAddress();

        if (empty($ipCustomer)) {
            $payment = $paymentDO->getPayment();
            $order = $payment->getOrder();
            $ipCustomer = $order->getXForwardedFor();
        }

        $result[CardInitSchemaDataRequest::DATA][AdditionalInitSchemaDataRequest::ADDITIONAL_DATA][self::DEVICE_DATA] =
        [
            self::REMOTE_IP         => $ipCustomer,
            // self::REMOTE_USER_AGENT => $this->headerClient->getHttpUserAgent(),
            self::DEVICE_ID         => $this->session->getSessionId(),
        ];

        $paymentInfo = $paymentDO->getPayment();

        $paymentInfo->setAdditionalInformation(
            self::DEVICE_DATA,
            $result[CardInitSchemaDataRequest::DATA]
            [AdditionalInitSchemaDataRequest::ADDITIONAL_DATA][self::DEVICE_DATA]
        );

        return $result;
    }
}
