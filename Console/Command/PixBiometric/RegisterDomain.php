<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Console\Command\PixBiometric;

use Getnet\PaymentMagento\Model\OpenFinanceManagement;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI: register the Pix by biometrics return URL (Open Finance merchant domain).
 */
class RegisterDomain extends Command
{
    public const STORE_ID = 'store-id';

    public const REDIRECT_URI = 'redirect-uri';

    /**
     * @var State
     */
    protected $state;

    /**
     * @var OpenFinanceManagement
     */
    protected $openFinanceManagement;

    /**
     * @param State                 $state
     * @param OpenFinanceManagement $openFinanceManagement
     */
    public function __construct(
        State $state,
        OpenFinanceManagement $openFinanceManagement
    ) {
        $this->state = $state;
        $this->openFinanceManagement = $openFinanceManagement;
        parent::__construct();
    }

    /**
     * Configure.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('getnet:pix-biometric:register-domain');
        $this->setDescription(
            'Register the Pix by biometrics return URL (Open Finance merchant domain) — applies to the whole seller'
        );
        $this->addOption(self::STORE_ID, null, InputOption::VALUE_OPTIONAL, 'Store Id', null);
        $this->addOption(
            self::REDIRECT_URI,
            null,
            InputOption::VALUE_OPTIONAL,
            'Return URL (https). Defaults to the configured override or the store return route',
            null
        );
        parent::configure();
    }

    /**
     * Execute.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->state->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $exc) {
            // Area code already set
            $exc->getMessage();
        }

        $storeId = $input->getOption(self::STORE_ID);
        $storeId = ($storeId === null) ? null : (int) $storeId;

        try {
            $result = $this->openFinanceManagement->registerDomain(
                $storeId,
                $input->getOption(self::REDIRECT_URI)
            );
        } catch (LocalizedException $exc) {
            $output->writeln(sprintf('<error>%s</error>', $exc->getMessage()));

            return Cli::RETURN_FAILURE;
        }

        $tag = $result['success'] ? 'info' : 'error';
        $output->writeln(sprintf('<%s>%s %s</%s>', $tag, $result['message'], $result['redirect_uri'], $tag));

        return $result['success'] ? Cli::RETURN_SUCCESS : Cli::RETURN_FAILURE;
    }
}
