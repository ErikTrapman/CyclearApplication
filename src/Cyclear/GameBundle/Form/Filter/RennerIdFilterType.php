<?php

/*
 * This file is part of the Cyclear-game package.
 *
 * (c) Erik Trapman <veggatron@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cyclear\GameBundle\Form\Filter;

class RennerIdFilterType extends \Symfony\Component\Form\AbstractType
{

    public function buildForm(\Symfony\Component\Form\FormBuilderInterface $builder, array $options)
    {
        $builder->add('renner', 'renner_selector', array(
            'required' => false,
            'label' => 'Naam / CQ-id'));
    }

    public function getName()
    {
        return 'renner_id_filter';
    }
}
