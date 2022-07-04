<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Console\Command\Marketplace;

use Getnet\PaymentMagento\Model\Console\Command\Marketplace\PaymentRelease;
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
    public const ORDER_ID = 'order-id';

    /**
     * @const string.
     */
    public const SUB_SELLER_ID = 'sub-seller-id';

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
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $date;

    /**
     * @var Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @param State                                                $state
     * @param PaymentRelease                                       $paymentRelease
     * @param \Magento\Framework\Stdlib\DateTime\DateTime          $date
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        State $state,
        PaymentRelease $paymentRelease,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    ) {
        $this->state = $state;
        $this->paymentRelease = $paymentRelease;
        $this->date = $date;
        $this->timezone = $timezone;
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

        $subSellerId = $input->getArgument(self::SUB_SELLER_ID);

        $date = $input->getArgument(self::DATE);
        $date = $this->convertDate($date);

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
                new InputArgument(self::DATE, InputArgument::OPTIONAL, 'Date'),
                new InputArgument(self::SUB_SELLER_ID, InputArgument::OPTIONAL, 'Sub Seller Id Getnet (id ext)'),
            ]
        );
        parent::configure();
    }

    /**
     * Convert Date.
     *
     * @param string|null $date
     *
     * @return string
     */
    public function convertDate(string $date = null): string
    {
        if ($date) {
            $date = str_replace('/', '-', $date);
            $defaultTimezone = $this->timezone->getDefaultTimezone();
            $configTimezone = $this->timezone->getConfigTimezone();
            $date = new \DateTime($date, new \DateTimeZone($configTimezone));
            $date->setTimezone(new \DateTimeZone($defaultTimezone));
        }

        if (!$date) {
            $date = new \DateTime();
        }

        return $date->format('Y-m-d\TH:i:s\Z');
    }
}
