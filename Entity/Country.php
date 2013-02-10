<?php

namespace Cyclear\GameBundle\Entity;

use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;

/**
 *
 * @ORM\Entity
 */
class Country implements Translatable
{
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     *
     * @ORM\Column(type="string", length=2) 
     */
    private $iso2; // varchar(2) NOT NULL default '',

    /**
     * @Gedmo\Translatable
     * @ORM\Column(nullable=true)
     */
    private $name;

    /**
     *
     * @Gedmo\Locale
     */
    private $locale;

    public function getId()
    {
        return $this->id;
    }

    public function getIso2()
    {
        return $this->iso2;
    }

    public function setIso2($iso2)
    {
        $this->iso2 = $iso2;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function setTranslatableLocale($locale)
    {
        $this->locale = $locale;
    }

    public function __toString()
    {
        return $this->getName();
    }
}