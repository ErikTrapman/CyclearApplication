<?php

namespace Cyclear\GameBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * RennerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RennerRepository extends EntityRepository
{

    public function findOneByNaam($naam)
    {
        return $this->findOneBy(array('naam' => $naam));
    }

    public function findOneByCQId($id)
    {
        return $this->findOneBy(array('cqranking_id' => $id));
    }

    public function findOneBySelectorString($rennerString)
    {
        $firstBracket = strpos($rennerString, '[');
        $lastBracket = strpos($rennerString, ']');
        $cqId = trim(substr($rennerString, 0, $firstBracket));
        $name = substr($rennerString, $firstBracket + 1, $lastBracket - $firstBracket - 1);

        return $this->findOneBy(array('naam' => $name, 'cqranking_id' => $cqId));
    }

    public function getPloeg($renner, $seizoen = null)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $contracts = $this->_em->getRepository("CyclearGameBundle:Contract")
                ->createQueryBuilder('c')
                ->where('c.renner = :renner')
                ->andWhere('c.eind IS NOT NULL')
                ->andWhere('c.seizoen = :seizoen')
                ->setParameters(array('renner' => $renner, 'seizoen' => $seizoen))
                ->getQuery()->getResult();
        if (empty($contracts)) {
            return null;
        }
        return $contracts[0];
    }

    public function findOneByJoinedByLastTransferOnOrBeforeDate($id, \DateTime $date)
    {

        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addEntityResult('Cyclear\GameBundle\Entity\Transfer', 't');
        //$rsm->addFieldResult('r', 'r_id', 'id');
        $cloneDate = clone $date;
        $cloneDate->setTime("23", "59", "59");
        $query = $this->getEntityManager()->createNativeQuery("SELECT * FROM transfer t
                LEFT JOIN renner r ON t.renner_id = r.id 
                LEFT JOIN ploeg p ON ploegnaar_id = p.id
                WHERE t.renner_id = :rennerid AND t.datum < :parsedatum 
                ORDER BY t.datum DESC LIMIT 1", $rsm)->setParameters(array('rennerid' => $id, 'parsedatum' => $cloneDate));
        $result = $query->getResult();
        if (count($result) == 0) {
            return null;
        }
        return $result[0];
    }

    public function findOneJoinedByPloegOnDate($renner, \DateTime $date)
    {

        /*
         * SELECT * FROM transfer 
          LEFT JOIN renner ON renner_id = renner.id
          LEFT JOIN ploeg ON ploegnaar_id = ploeg.id
          WHERE renner_id = 18367 AND DATE(datum) < '2011-10-15'
          ORDER BY datum DESC LIMIT 1
         */
        //echo $renner->getId();
        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addEntityResult('Cyclear\GameBundle\Entity\Transfer', 't');
        $rsm->addFieldResult('r', 'r.id', 'id');
        $cloneDate = clone $date;
        $cloneDate->setTime("23", "59", "59");
        $query = $this->getEntityManager()->createNativeQuery("SELECT * FROM transfer t
                LEFT JOIN renner r ON t.renner_id = r.id 
                LEFT JOIN ploeg p ON ploegnaar_id = p.id
                WHERE t.renner_id = :rennerid AND t.datum < '2011-10-15'
                ORDER BY t.datum DESC LIMIT 1", $rsm)->setParameters(array('rennerid' => $renner->getId())); // , 'parsedatum' => $cloneDate));
        return $query->getResult();
    }

    public function findOneJoinedByPloegOnDate2($renner, \DateTime $date)
    {

        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addEntityResult('Cyclear\GameBundle\Entity\Renner', 'r');
        $rsm->addFieldResult('r', 'r_id', 'id');
        //$rsm->addJoinedEntityResult('Cyclear\GameBundle\Entity\Ploeg' , 'p', 'r', 'ploeg');
        //$rsm->addFieldResult('p', 'p_id', 'id');
        //$rsm->addFieldResult('p', 'p_naam', 'naam');

        $query = $this->getEntityManager()->createNativeQuery("
                SELECT r.id AS r_id FROM renner r
                    LEFT JOIN transfer AS t ON t.renner_id = :renner AND t.datum < ':datum'
                    LEFT JOIN ploeg p ON t.ploegnaar_id = p.id
                    WHERE r.id = :renner2
                    ORDER BY t.id DESC LIMIT 1", $rsm)
            ->setParameter('datum', $date->format('Y-m-d 00:00:00'))
            ->setParameter('renner', $renner->getId())
            ->setParameter('renner2', $renner->getId())
        ;
        $query->setFetchMode("Cyclear\GameBundle\Entity\Renner", "ploeg", "LAZY");
        $r = $query->getSingleResult();
        if (!is_null($r)) {
            echo $r->getPloeg()->getId();
            echo $r->getPloeg()->getNaam();
            die(__METHOD__);
        }
        //var_dump($r);
        return $r;

        // SELECT r t FROM renner
//LEFT JOIN transfer ON renner_id = 18367 AND datum < '2012-02-04 00:00:00' 
//WHERE renner.id = 18367
//ORDER BY transfer.id DESC LIMIT 1

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('r', 't', 'p')
            ->from('CyclearGameBundle:Renner', 'r')
            ->join('r.transfers', 't', 'ON', 'r.id = :rennerid AND datum < :datum')
            ->join('r.ploeg', 'p', 'ON', 'ploegNaar = p.id')
            ->setParameter('datum', $date->format('Y-m-d 00:00:00'))
            ->setParameter('rennerid', $renner->getId())
            //->where('t.renner = :renner')
            //->setParameter('renner', $renner)
            ->setMaxResults(1)
            ->orderBy('t.id', 'DESC');

        try {
            return $query->getQuery()->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        }
    }
}