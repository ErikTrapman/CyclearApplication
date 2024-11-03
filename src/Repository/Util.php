<?php

namespace App\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Query\Parameter;

class Util
{
    public static function buildParameters(array $params): ArrayCollection
    {
        $objects = [];
        foreach ($params as $key => $value) {
            $objects[] = new Parameter($key, $value);
        }

        return new ArrayCollection($objects);
    }
}
