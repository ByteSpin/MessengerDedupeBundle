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

namespace ByteSpin\MessengerDedupeBundle\Messenger\Stamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

class MasterStamp implements StampInterface
{
    private string $master;

    public function __construct(string $master)
    {
        $this->master = $master;
    }

    public function getMaster(): string
    {
        return $this->master;
    }
}