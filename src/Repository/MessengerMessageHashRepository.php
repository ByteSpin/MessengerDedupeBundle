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

namespace ByteSpin\MessengerDedupeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ByteSpin\MessengerDedupeBundle\Entity\MessengerMessageHash;

/**
 * @extends ServiceEntityRepository<MessengerMessageHash>
 *
 * @method MessengerMessageHash|null find($id, $lockMode = null, $lockVersion = null)
 * @method MessengerMessageHash|null findOneBy(array $criteria, array $orderBy = null)
 * @method MessengerMessageHash[]    findAll()
 * @method MessengerMessageHash[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessengerMessageHashRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessengerMessageHash::class);
    }
}
