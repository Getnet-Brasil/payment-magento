<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Gateway\Request\Pix\Biometric;

use Getnet\PaymentMagento\Gateway\Http\Api;
use Getnet\PaymentMagento\Gateway\SubjectReader;
use Magento\Framework\DataObject\IdentityGeneratorInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

/**
 * Order id and idempotency key for the Biometric Pix charge.
 *
 * The biometric endpoint only accepts a GUID as order_id (the increment id returns 400), so
 * a GUID is generated per order and kept on the payment, together with the idempotency key,
 * so a retry of the same order reuses both and never creates a second charge.
 */
class OrderIdDataRequest implements BuilderInterface
{
    public const ORDER_ID = 'order_id';

    public const IDEMPOTENCY_KEY = 'idempotency_key';

    public const HEADER_IDEMPOTENCY_KEY = 'idempotency-key';

    /**
     * Payment additional information keys.
     */
    public const INFO_GETNET_ORDER_ID = 'getnet_order_id';

    public const INFO_IDEMPOTENCY_KEY = 'getnet_idempotency_key';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @var IdentityGeneratorInterface
     */
    protected $identityGenerator;

    /**
     * @param SubjectReader              $subjectReader
     * @param IdentityGeneratorInterface $identityGenerator
     */
    public function __construct(
        SubjectReader $subjectReader,
        IdentityGeneratorInterface $identityGenerator
    ) {
        $this->subjectReader = $subjectReader;
        $this->identityGenerator = $identityGenerator;
    }

    /**
     * Build.
     *
     * @param array $buildSubject
     *
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = $this->subjectReader->readPayment($buildSubject)->getPayment();

        $orderId = $payment->getAdditionalInformation(self::INFO_GETNET_ORDER_ID)
            ?: $this->identityGenerator->generateId();
        $idempotencyKey = $payment->getAdditionalInformation(self::INFO_IDEMPOTENCY_KEY)
            ?: $this->identityGenerator->generateId();

        $payment->setAdditionalInformation(self::INFO_GETNET_ORDER_ID, $orderId);
        $payment->setAdditionalInformation(self::INFO_IDEMPOTENCY_KEY, $idempotencyKey);

        return [
            self::ORDER_ID        => $orderId,
            self::IDEMPOTENCY_KEY => $idempotencyKey,
            Api::EXTRA_HEADERS => [
                self::HEADER_IDEMPOTENCY_KEY => $idempotencyKey,
            ],
        ];
    }
}
