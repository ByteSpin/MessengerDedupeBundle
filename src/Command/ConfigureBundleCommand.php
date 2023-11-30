<?php

/**
 * This file is part of the ByteSpin/MessengerDedupeBundle project.
 * The project is hosted on GitHub at:
 *  https://github.com/ByteSpin/MessengerDedupeBundle.git
 *
 * Copyright (c) Greg LAMY <greg@bytespin.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ByteSpin\MessengerDedupeBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ByteSpin\MessengerDedupeBundle\Scripts\PostInstallScript;

class ConfigureBundleCommand extends Command
{
    protected static $defaultName = 'bytespin:configure-messenger-dedupe';

    protected function configure(): void
    {
        $this
            ->setDescription('Configure the ByteSpin Messenger Dedupe Bundle.')
            ->setHelp('This command allows you to configure the ByteSpin Messenger Dedupe Bundle...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        PostInstallScript::postInstall();

        return Command::SUCCESS;
    }
}