<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\PaymentMagento\Model\Ui\Vault\Adminhtml;

use Getnet\PaymentMagento\Model\Ui\ConfigProviderCc;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;

/**
 * Class TokenUiComponentProvider - Defines properties of the payment form.
 *
 * @SuppressWarnings(PHPCPD)
 */
class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    /**
     * @var TokenUiComponentInterfaceFactory
     */
    private $componentFactory;

    /**
     * @var Json
     */
    protected $json;

    /**
     * TokenUiComponentProvider constructor.
     *
     * @param TokenUiComponentInterfaceFactory $componentFactory
     * @param Json                             $json
     */
    public function __construct(
        TokenUiComponentInterfaceFactory $componentFactory,
        Json $json
    ) {
        $this->componentFactory = $componentFactory;
        $this->json = $json;
    }

    /**
     * @inheritdoc
     */
    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $details = $this->json->unserialize($paymentToken->getTokenDetails());
        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code'                                                   => ConfigProviderCc::VAULT_CODE,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS     => $details,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                    // phpcs:ignore Generic.Files.LineLength
                    'template'                                               => 'Getnet_PaymentMagento::form/vault.phtml',
                ],
                'name' => Template::class,
            ]
        );

        return $component;
    }
}
