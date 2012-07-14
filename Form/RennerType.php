<?php

namespace Cyclear\GameBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class RennerType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('naam')
            ->add('ploeg', 'entity', array('required'=>false, 'class' => 'Cyclear\GameBundle\Entity\Ploeg'))
            ->add('cqranking_id')
        ;
    }

    public function getName()
    {
        return 'cyclear_gamebundle_rennertype';
    }
}
