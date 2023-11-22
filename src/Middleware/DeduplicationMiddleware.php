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

namespace ByteSpin\MessengerDedupeBundle\Middleware;

use ByteSpin\MessengerDedupeBundle\Messenger\Stamp\HashStamp;
use Doctrine\ORM\EntityManagerInterface;
use ByteSpin\MessengerDedupeBundle\Entity\MessengerMessageHash;
use ByteSpin\MessengerDedupeBundle\Repository\MessengerMessageHashRepository;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Middleware\StackInterface;

readonly class DeduplicationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessengerMessageHashRepository $hashRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {

        if ($envelope->last(ReceivedStamp::class)) {
            // If the message has a ReceivedStamp, it means it has been received from transport.
            // In this case, we skip any further processing in this middleware and pass the
            // envelope to the next middleware in the stack for handling.
            return $stack->next()->handle($envelope, $stack);
        }

        /** @var HashStamp|null $hashStamp */
        if ($envelope->last(HashStamp::class)) {
            $hash = $envelope->last(HashStamp::class)->getHash();

            if ($this->hashRepository->findOneBy(['hash' => $hash])) {
                // ignore message if a similar hash is found in the database
                return $envelope;
            } else {
                // save hash in database
                $hashData = new MessengerMessageHash();
                $hashData->setHash($hash);
                $this->entityManager->persist($hashData);
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }
        return $stack->next()->handle($envelope, $stack);
    }
}
