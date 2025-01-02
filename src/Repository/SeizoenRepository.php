<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Seizoen;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SeizoenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seizoen::class);
    }

    public function getCurrent(): ?Seizoen
    {
        return $this->createQueryBuilder('s')->where('s.current = 1')->getQuery()->getOneOrNullResult();
    }

    public function getArchived(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.closed = 1')
            ->orderBy('s.start', 'ASC')
            ->getQuery()->getResult();
    }

    public function getMaxTransfers(): int
    {
        if (!$current = $this->getCurrent()) {
            return 0;
        }

        return $current->getMaxTransfers();
    }
}
