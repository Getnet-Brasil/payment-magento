<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Response;

use Getnet\PaymentMagento\Gateway\Config\ConfigCc;
use InvalidArgumentException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class VaultDetailsHandler implements HandlerInterface
{
    /**
     * Response Pay Credit - Block name.
     */
    public const CREDIT = 'credit';

    /**
     * Response Brand - Block name.
     */
    public const BRAND = 'brand';
    /**
     * @var PaymentTokenInterfaceFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $payExtensionFactory;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var ConfigCc
     */
    private $configCc;

    /**
     * @param Json                                  $json
     * @param ObjectManagerInterface                $objectManager
     * @param OrderPaymentExtensionInterfaceFactory $payExtensionFactory
     * @param ConfigCc                              $configCc
     * @param PaymentTokenFactoryInterface          $paymentTokenFactory
     */
    public function __construct(
        Json $json,
        ObjectManagerInterface $objectManager,
        OrderPaymentExtensionInterfaceFactory $payExtensionFactory,
        ConfigCc $configCc,
        PaymentTokenFactoryInterface $paymentTokenFactory = null
    ) {
        if ($paymentTokenFactory === null) {
            $paymentTokenFactory = $objectManager->get(PaymentTokenFactoryInterface::class);
        }

        $this->objectManager = $objectManager;
        $this->payExtensionFactory = $payExtensionFactory;
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->json = $json;
        $this->configCc = $configCc;
    }

    /**
     * Handle.
     *
     * @param array $handlingSubject
     * @param array $response
     *
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new InvalidArgumentException('Payment data object should be provided');
        }

        $paymentDO = $handlingSubject['payment'];

        $payment = $paymentDO->getPayment();
        $paymentToken = $this->getVaultPaymentToken($payment, $response);
        if (null !== $paymentToken) {
            $extensionAttributes = $this->getExtensionAttributes($payment);
            $extensionAttributes->setVaultPaymentToken($paymentToken);
        }
    }

    /**
     * Get vault payment token entity.
     *
     * @param InfoInterface $payment
     * @param array         $response
     *
     * @return PaymentTokenInterface|null
     */
    protected function getVaultPaymentToken($payment, $response)
    {
        $ccNumberToken = $payment->getAdditionalInformation('cc_public_id');
        $ccLast4 = $payment->getAdditionalInformation('cc_number');
        $ccType = $payment->getAdditionalInformation('cc_type');
        $ccExpMonth = $payment->getAdditionalInformation('cc_exp_month');

        $ccExpYear = $payment->getAdditionalInformation('cc_exp_year');
        if (empty($ccNumberToken)) {
            return null;
        }

        $ccType = $this->mapperCcType($ccType);
        if (!$ccType) {
            $ccType = $response[self::CREDIT][self::BRAND];
        }

        $ccLast4 = preg_replace('/[^0-9]/', '', (string) $ccLast4);
        $paymentToken = $this->paymentTokenFactory->create();
        $paymentToken->setGatewayToken($ccNumberToken);
        $paymentToken->setExpiresAt(strtotime('+1 year'));
        $paymentToken->setType(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        // phpcs:ignore Generic.Files.LineLength
        $details = ['cc_last4' => $ccLast4, 'cc_exp_year' => $ccExpYear, 'cc_exp_month' => $ccExpMonth, 'cc_type' => $ccType];
        $paymentToken->setTokenDetails($this->json->serialize($details));

        return $paymentToken;
    }

    /**
     * Get payment extension attributes.
     *
     * @param InfoInterface $payment
     *
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->payExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }

        return $extensionAttributes;
    }

    /**
     * Get Type Cc by response payment.
     *
     * @param string $type
     *
     * @return string
     */
    public function mapperCcType($type)
    {
        $type = ucfirst($type);
        $mapper = $this->configCc->getCcTypesMapper();

        return $mapper[$type];
    }
}
