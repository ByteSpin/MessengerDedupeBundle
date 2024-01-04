<?php

namespace ByteSpin\MessengerDedupeBundle\MessageHandler;

use AllowDynamicProperties;
use ByteSpin\MessengerDedupeBundle\Entity\MessengerMessageHash;
use ByteSpin\MessengerDedupeBundle\Model\RemoveDedupeHash;
use ByteSpin\MessengerDedupeBundle\Repository\MessengerMessageHashRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AllowDynamicProperties] #[AsMessageHandler]
class RemoveDedupeHashHandler
{
    public function __construct(
        private readonly MessengerMessageHashRepository $hashRepository,
        private readonly ManagerRegistry $managerRegistry,
    ) {
        $this->entityManager = $this->managerRegistry->getManagerForClass(MessengerMessageHash::class);
    }

    public function __invoke(RemoveDedupeHash $message): void
    {
        if ($message->hash) {
            if ($hashData = $this->hashRepository->findOneBy(['hash' =>$message->hash])) {
                // delete message hash from database
                $this->entityManager->remove($hashData);
                $this->entityManager->flush();
                $this->entityManager->clear();
            }

        }
    }
}
