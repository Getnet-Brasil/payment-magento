<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Gateway\Request\Card\Data\Additional;

use Getnet\PaymentMagento\Gateway\SubjectReader;
use InvalidArgumentException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\Encryption\Encryptor;
use Getnet\PaymentMagento\Gateway\Config\Config;
use Getnet\PaymentMagento\Gateway\Request\Card\CardInitSchemaDataRequest;

/**
 * Class Additional Init Schema Data Request - Payment amount structure.
 */
class AdditionalInitSchemaDataRequest implements BuilderInterface
{
    /**
     * Additional Data Block Name.
     */
    public const ADDITIONAL_DATA = 'additional_data';

    /**
     * @var SubjectReader
     */
    protected $subjectReader;

    /**
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader,
    ) {
        $this->subjectReader = $subjectReader;
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
            throw new InvalidArgumentException(__('Payment data object should be provided'));
        }

        $result[CardInitSchemaDataRequest::DATA][self::ADDITIONAL_DATA] = [];

        return $result;
    }
}
