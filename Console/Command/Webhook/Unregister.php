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
 * Delete Global API webhook subscriptions (and optionally re-register the current ones).
 */
class Unregister extends Command
{
    /**
     * @const string.
     */
    public const STORE_ID = 'store-id';

    /**
     * @const string.
     */
    public const EVENT = 'event';

    /**
     * @const string.
     */
    public const RESYNC = 'resync';

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
        $this->setName('getnet:webhook:unregister');
        $this->setDescription(
            'Delete Getnet Global API webhook subscriptions (default: all known events; --resync re-registers)'
        );
        $this->addOption(
            self::STORE_ID,
            null,
            InputOption::VALUE_OPTIONAL,
            'Store Id',
            null
        );
        $this->addOption(
            self::EVENT,
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Event name to delete (repeatable). When omitted, deletes every known event (current + legacy).'
        );
        $this->addOption(
            self::RESYNC,
            null,
            InputOption::VALUE_NONE,
            'After deleting all known events, re-register the current ones (full re-sync)'
        );
        $this->addOption(
            self::CALLBACK_URL,
            null,
            InputOption::VALUE_OPTIONAL,
            'Callback URL override used on re-sync (e.g. a public tunnel/relay)',
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
        $events = $input->getOption(self::EVENT) ?: null;
        $callbackUrl = $input->getOption(self::CALLBACK_URL);

        try {
            if ($input->getOption(self::RESYNC)) {
                $results = $this->webhookManagement->sync($storeId, $callbackUrl);

                return $this->renderSync($results, $output);
            }

            $results = $this->webhookManagement->unregister($storeId, $events);
        } catch (LocalizedException $exc) {
            $output->writeln(sprintf('<error>%s</error>', $exc->getMessage()));

            return Cli::RETURN_FAILURE;
        }

        return $this->renderResults($results, $output);
    }

    /**
     * Render a re-sync result (unregister + register sections).
     *
     * @param array           $results
     * @param OutputInterface $output
     *
     * @return int
     */
    private function renderSync(array $results, OutputInterface $output): int
    {
        $output->writeln('<comment>Unregister:</comment>');
        $hasError = $this->renderResults($results['unregistered'] ?? [], $output) === Cli::RETURN_FAILURE;

        $output->writeln('<comment>Register:</comment>');
        $hasError = ($this->renderResults($results['registered'] ?? [], $output) === Cli::RETURN_FAILURE) || $hasError;

        return $hasError ? Cli::RETURN_FAILURE : Cli::RETURN_SUCCESS;
    }

    /**
     * Render a flat result map by event.
     *
     * @param array           $results
     * @param OutputInterface $output
     *
     * @return int
     */
    private function renderResults(array $results, OutputInterface $output): int
    {
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
