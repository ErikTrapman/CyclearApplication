<?php

namespace Cyclear\GameBundle\Entity;

use Doctrine\ORM\EntityRepository;

class UitslagRepository extends EntityRepository
{

    public function getPuntenByPloeg($seizoen = null, $ploeg = null)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $options = array(":seizoen_id" => $seizoen->getId());
        $ploegWhere = null;
        if(null !== $ploeg){
            $ploegWhere = ' AND p.id = :ploeg_id';
            $options['ploeg_id'] = $ploeg->getId();
        }
        $sql = sprintf("SELECT p.id AS id, p.naam AS naam, p.afkorting AS afkorting, 
                ( SELECT IFNULL(SUM(u.ploegPunten),0) 
                FROM Uitslag u 
                INNER JOIN Wedstrijd w ON u.wedstrijd_id = w.id 
                WHERE w.seizoen_id = :seizoen_id AND u.ploeg_id = p.id ) AS punten 
                FROM Ploeg p 
                WHERE p.seizoen_id = :seizoen_id %s
                ORDER BY punten DESC, p.afkorting ASC
                ",$ploegWhere);
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($options);
        return $stmt->fetchAll(\PDO::FETCH_NAMED);
    }

    public function getPuntenForPloeg($seizoen = null, $ploeg)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        
    }

    public function getPuntenByPloegForPeriode(Periode $periode, $seizoen = null)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $start = clone $periode->getStart();
        $start->setTime('00', '00', '00');
        $end = clone $periode->getEind();
        $end->setTime('23', '59', '59');

        $sql = "SELECT *,
                    ( SELECT IFNULL(SUM(u.ploegPunten),0)
                    FROM Uitslag u 
                    INNER JOIN Wedstrijd w ON w.id = u.wedstrijd_id
                    WHERE w.datum BETWEEN :start AND :end AND u.ploeg_id = p.id
                     ) AS punten
                FROM Ploeg p WHERE p.seizoen_id = :seizoen_id
                GROUP BY p.id
                ORDER BY punten DESC, p.afkorting ASC
                ";
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(":seizoen_id" => $seizoen->getId(), ":start" => $start->format('Y-m-d'), ":end" => $end->format('Y-m-d')));
        $res = $stmt->fetchAll(\PDO::FETCH_NAMED);
        return $res;
    }

    public function getCountForPosition($seizoen = null, $pos = 1)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }

        $sql = "SELECT *,
                    IFNULL(( SELECT SUM(IF(u.positie = :pos,1,0)) AS freqByPos
                    FROM Uitslag u
                    INNER JOIN Wedstrijd w ON u.wedstrijd_id = w.id
                    WHERE u.ploeg_id = p.id AND w.seizoen_id = :seizoen_id
                     ),0) AS freqByPos
                FROM Ploeg p WHERE p.seizoen_id = :seizoen_id
                GROUP BY p.id
                ORDER BY freqByPos DESC, p.afkorting ASC";
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(":pos" => $pos, ":seizoen_id" => $seizoen->getId()));
        return $stmt->fetchAll(\PDO::FETCH_NAMED);
    }

    public function getPuntenForRenner($renner, $seizoen = null)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $qb = $this->getPuntenForRennerQb($renner);
        $qb->andWhere("w.seizoen = :seizoen");
        $qb->setParameters(array('seizoen' => $seizoen, 'renner' => $renner));
        return $qb->getQuery()->getResult();
    }

    public function getTotalPuntenForRenner($renner, $seizoen = null)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $qb = $this->getPuntenForRennerQb($renner);
        $qb->andWhere("w.seizoen = :seizoen");
        $qb->setParameters(array('seizoen' => $seizoen, 'renner' => $renner));
        $qb->add('select', 'SUM(u.rennerPunten)');
        return $qb->getQuery()->getSingleScalarResult();
    }

    public function getPuntenForRennerWithPloeg($renner, $ploeg, $seizoen = null)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $qb = $this->getPuntenForRennerQb($renner);
        $qb->andWhere("w.seizoen = :seizoen")->andWhere('u.ploeg = :ploeg');
        $qb->setParameters(array('seizoen' => $seizoen, 'ploeg' => $ploeg, 'renner' => $renner));
        $qb->add('select', 'SUM(u.ploegPunten)');
        return $qb->getQuery()->getSingleScalarResult();
    }

    private function getPuntenForRennerQb()
    {
        $qb = $this->createQueryBuilder("u")
            ->join('u.wedstrijd', 'w')
            ->where("u.renner = :renner")
            ->orderBy("u.id", "DESC")
        ;
        return $qb;
    }

    public function getPuntenWithRenners($seizoen = null, $limit = 20)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $qb = $this->createQueryBuilder('u')
            ->join('u.wedstrijd', 'w')
            ->where('w.seizoen =:seizoen')
            ->leftJoin('u.renner', 'r')
            ->groupBy('u.renner')->add('select', 'IFNULL(SUM(u.rennerPunten),0) AS punten', true)
            ->setMaxResults($limit)
            ->setParameters(array('seizoen' => $seizoen))
            ->orderBy('punten DESC, r.naam', 'ASC')
        ;
        $ret = array();
        foreach ($qb->getQuery()->getResult() as $result) {
            $ret[] = array(0 => $result[0]->getRenner(), 'punten' => $result['punten']);
        }
        return $ret;
    }

    public function getPuntenWithRennersNoPloeg($seizoen = null, $limit = 20)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $rennersWithPloeg = array();
        foreach ($this->_em->getRepository("CyclearGameBundle:Renner")->getRennersWithPloeg() as $renner) {
            $rennersWithPloeg [] = $renner->getId();
        }
        $qb = $this->createQueryBuilder('u')
            ->join('u.wedstrijd', 'w')
            ->where('w.seizoen =:seizoen')
            ->leftJoin('u.renner', 'r')
            ->groupBy('u.renner')->add('select', 'IFNULL(SUM(u.rennerPunten),0) AS punten', true)
            ->setMaxResults($limit)
            ->setParameters(array('seizoen' => $seizoen))
            ->orderBy('punten DESC, r.naam', 'ASC')
        ;
        if (!empty($rennersWithPloeg)) {
            $qb->andWhere($qb->expr()->notIn('u.renner', $rennersWithPloeg));
        }
        $ret = array();
        foreach ($qb->getQuery()->getResult() as $result) {
            $ret[] = array(0 => $result[0]->getRenner(), 'punten' => $result['punten']);
        }
        return $ret;
    }

    public function getPuntenByPloegForDraftTransfers($seizoen = null)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $transferSql = "SELECT t.renner_id FROM Transfer t WHERE t.transferType = ".Transfer::DRAFTTRANSFER." AND t.ploegNaar_id = p.id AND t.seizoen_id = :seizoen_id";

        $sql = sprintf("SELECT p.id AS id, p.naam AS naam, p.afkorting AS afkorting,
                ( SELECT IFNULL(SUM(u.rennerPunten),0) FROM Uitslag u INNER JOIN Wedstrijd w ON u.wedstrijd_id = w.id WHERE w.seizoen_id = :seizoen_id AND u.renner_id IN ( %s ) ) AS punten 
                FROM Ploeg p WHERE p.seizoen_id = :seizoen_id 
                ORDER BY punten DESC, p.afkorting ASC
                ", $transferSql);
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(":seizoen_id" => $seizoen->getId(), 'transfertype_draft' => Transfer::DRAFTTRANSFER));
        return $stmt->fetchAll(\PDO::FETCH_NAMED);
    }

    public function getPuntenByPloegForUserTransfers($seizoen = null)
    {
        if (null === $seizoen) {
            $seizoen = $this->_em->getRepository("CyclearGameBundle:Seizoen")->getCurrent();
        }
        $transfers = "SELECT DISTINCT t.renner_id FROM Transfer t 
            WHERE t.transferType != ".Transfer::DRAFTTRANSFER." AND t.ploegNaar_id = p.id AND t.seizoen_id = :seizoen_id
                AND t.renner_id NOT IN ( SELECT t.renner_id FROM Transfer t WHERE t.transferType = ".Transfer::DRAFTTRANSFER." AND t.ploegNaar_id = p.id AND t.seizoen_id = :seizoen_id )
                ";
        $sql = sprintf("SELECT p.id AS id, p.naam AS naam, p.afkorting AS afkorting,
                (SELECT IFNULL(SUM(u.ploegPunten),0) FROM Uitslag u INNER JOIN Wedstrijd w ON u.wedstrijd_id = w.id WHERE w.seizoen_id = :seizoen_id AND u.renner_id IN (%s)) AS punten
                FROM Ploeg p WHERE p.seizoen_id = :seizoen_id
                ORDER BY punten DESC, p.afkorting ASC
                ", $transfers);
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(":seizoen_id" => $seizoen->getId(), 'transfertype_draft' => Transfer::DRAFTTRANSFER));
        return $stmt->fetchAll(\PDO::FETCH_NAMED);
    }
}
