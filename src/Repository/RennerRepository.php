<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\Contract;
use App\Entity\Ploeg;
use App\Entity\Renner;
use App\Entity\Seizoen;
use App\Entity\Transfer;
use App\Entity\Uitslag;
use Doctrine\ORM\EntityRepository;

/**
 * RennerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RennerRepository extends EntityRepository
{
    public function findOneByNaam($naam): object|null
    {
        return $this->findOneBy(['naam' => $naam]);
    }

    /**
     * @param mixed $id
     * @return object|Renner|null
     */
    public function findOneByCQId($id)
    {
        return $this->findOneBy(['cqranking_id' => $id]);
    }

    public function findOneBySelectorString($rennerString): object|null
    {
        $firstBracket = strpos($rennerString, '[');
        $lastBracket = strpos($rennerString, ']');
        $cqId = trim(substr($rennerString, 0, $firstBracket));
        $name = substr($rennerString, $firstBracket + 1, $lastBracket - $firstBracket - 1);

        return $this->findOneBy(['naam' => $name, 'cqranking_id' => $cqId]);
    }

    public function getPloeg($renner, $seizoen = null)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository(Seizoen::class)->getCurrent();
        }
        if (is_numeric($renner)) {
            $renner = $this->_em->getRepository(Renner::class)->find($renner);
        }
        $contract = $this->_em->getRepository(Contract::class)->getCurrentContract($renner, $seizoen);
        if (null === $contract) {
            return null;
        }
        return $contract->getPloeg();
    }

    /**
     * @return bool
     */
    public function isDraftTransfer(Renner $renner, Ploeg $ploeg)
    {
        return (bool)$this->_em->getRepository(Transfer::class)->hasDraftTransfer($renner, $ploeg);
    }

    /**
     * @psalm-return list<mixed>
     * @param mixed|null $seizoen
     */
    public function getRennersWithPloeg($seizoen = null): array
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository(Seizoen::class)->getCurrent();
        }
        $rennersWithPloeg = [];
        foreach ($this->_em->getRepository(Contract::class)
                     ->createQueryBuilder('c')
                     ->where('c.seizoen = :seizoen')
                     ->andWhere('c.eind IS NULL')->setParameter('seizoen', $seizoen)
                     ->getQuery()->getResult() as $contract) {
            $rennersWithPloeg[] = $contract->getRenner();
        }
        return $rennersWithPloeg;
    }

    /**
     * @param null $seizoen
     * @param bool $excludeWithTeam
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getRennersWithPunten($seizoen = null, $excludeWithTeam = false)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository(Seizoen::class)->getCurrent();
        }
        $puntenQb = $this->_em->getRepository(Uitslag::class)
            ->createQueryBuilder('u')
            ->select('SUM(u.rennerPunten)')
            ->innerJoin('u.wedstrijd', 'w')
            ->where('u.renner = r')
            ->andWhere('w.seizoen = :seizoen')//    ->setParameter('seizoen', $seizoen)
        ;
        $teamQb = $this->_em->getRepository(Contract::class)
            ->createQueryBuilder('c')
            ->select('p.afkorting')
            ->innerJoin('c.ploeg', 'p')
            ->where('c.seizoen = :seizoen')
            ->andWhere('c.eind IS NULL')
            ->andWhere('c.renner = r')// ->setParameter('seizoen', $seizoen)
        ;
        $qb = $this->createQueryBuilder('r')
            ->addSelect('IFNULL((' . $puntenQb->getDQL() . '), 0) AS punten')
            ->leftJoin('r.country', 'cty')->addSelect('cty')
            ->orderBy('punten', 'DESC, r.naam ASC');
        if (true === $excludeWithTeam) {
            $qb->andHaving('IFNULL((' . $teamQb->getDQL() . '), -1) < 0');
        } else {
            $qb->addSelect('(' . $teamQb->getDQL() . ') AS team');
        }
        return $qb->setParameter('seizoen', $seizoen); // ->setMaxResults(20)->getQuery()->getResult();
    }
}
