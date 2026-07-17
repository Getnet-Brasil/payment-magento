<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\PaymentMagento\Console\Command\Webhook;

use Getnet\PaymentMagento\Model\WebhookManagement;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Register Global API webhook subscriptions for the notification endpoint.
 */
class Register extends Command
{
    /**
     * @const string.
     */
    public const STORE_ID = 'store-id';

    /**
     * @const string.
     */
    public const CALLBACK_URL = 'callback-url';

    /**
     * @var State
     */
    protected $state;

    /**
     * @var WebhookManagement
     */
    protected $webhookManagement;

    /**
     * @param State             $state
     * @param WebhookManagement $webhookManagement
     */
    public function __construct(
        State $state,
        WebhookManagement $webhookManagement
    ) {
        $this->state = $state;
        $this->webhookManagement = $webhookManagement;
        parent::__construct();
    }

    /**
     * Configure command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->setName('getnet:webhook:register');
        $this->setDescription('Register Getnet Global API webhook subscriptions (notification callback)');
        $this->addOption(
            self::STORE_ID,
            null,
            InputOption::VALUE_OPTIONAL,
            'Store Id',
            null
        );
        $this->addOption(
            self::CALLBACK_URL,
            null,
            InputOption::VALUE_OPTIONAL,
            'Callback URL override (e.g. a public tunnel/relay when the store is not publicly reachable)',
            null
        );
        parent::configure();
    }

    /**
     * Execute command.
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
        $callbackUrl = $input->getOption(self::CALLBACK_URL);

        try {
            $results = $this->webhookManagement->register($storeId, $callbackUrl);
        } catch (LocalizedException $exc) {
            $output->writeln(sprintf('<error>%s</error>', $exc->getMessage()));

            return Cli::RETURN_FAILURE;
        }

        $hasError = false;

        foreach ($results as $event => $result) {
            if ($result['success']) {
                $output->writeln(sprintf('<info>%s: %s</info>', $event, $result['message']));
                continue;
            }

            $hasError = true;
            $output->writeln(sprintf('<error>%s: %s</error>', $event, $result['message']));
        }

        return $hasError ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
    }
}
