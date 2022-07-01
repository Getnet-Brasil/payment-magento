<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Console\Command\Marketplace;

use Getnet\PaymentMagento\Model\Console\Command\Marketplace\PaymentRelease as PaymentRelease;
use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Payment Release with Getnet.
 */
class Release extends Command
{
    /**
     * @const string.
     */
    public const ORDER_ID = 'order_id';

    /**
     * @const string.
     */
    public const SUB_SELLER_ID = 'sub_seller_id';

    /**
     * @const string.
     */
    public const DATE = 'date';

    /**
     * @var PaymentRelease
     */
    protected $paymentRelease;

    /**
     * @var State
     */
    protected $state;

    /**
     * @param State          $state
     * @param PaymentRelease $paymentRelease
     */
    public function __construct(
        State $state,
        PaymentRelease $paymentRelease
    ) {
        $this->state = $state;
        $this->paymentRelease = $paymentRelease;
        parent::__construct();
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
        $this->paymentRelease->setOutput($output);

        $orderId = (int) $input->getArgument(self::ORDER_ID);
        $subSellerId = (int) $input->getArgument(self::SUB_SELLER_ID);
        $date = $input->getArgument(self::DATE);
        
        $this->paymentRelease->create($orderId, $date, $subSellerId);
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('getnet:marketplace:payment_release');
        $this->setDescription('Release of payment to the sub-seller on Getnet');
        $this->setDefinition(
            [
                new InputArgument(self::ORDER_ID, InputArgument::REQUIRED, 'Order Id'),
                new InputArgument(self::DATE, InputArgument::REQUIRED, 'Date'),
                new InputArgument(self::SUB_SELLER_ID, InputArgument::OPTIONAL, 'Sub Seller Id Getnet (id ext)'),
            ]
        );
        parent::configure();
    }
}
