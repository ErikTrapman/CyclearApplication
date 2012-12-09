<?php

namespace Cyclear\GameBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Entity\User as BaseUser;

/**
 * Cyclear\GameBundle\Entity\User
 *
 * @ORM\Table(name="User")
 * @ORM\Entity(repositoryClass="Cyclear\GameBundle\Entity\UserRepository")
 */
class User extends BaseUser implements \Serializable
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * 
     * @ORM\OneToMany(
     *  targetEntity="Cyclear\GameBundle\Entity\Ploeg", mappedBy="user"
     * )
     */
    private $ploeg;

    public function __construct()
    {
        $this->ploeg = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    public function serialize()
    {
        return serialize(array(
                $this->id,
            ));
    }

    public function unserialize($data)
    {
        list(
            $this->id
            ) = unserialize($data);
    }

    public function getPloeg()
    {
        return $this->ploeg;
    }

    public function setPloeg($ploeg)
    {
        $this->ploeg = $ploeg;
    }

    public function getPloegBySeizoen($seizoen)
    {
        foreach($this->getPloeg() as $ploeg){
            if($ploeg->getSeizoen() === $seizoen){
                return $ploeg;
            }
        }
        return null;
    }
}