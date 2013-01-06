<?php

namespace Cyclear\GameBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PloegType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('naam')
            ->add('afkorting')
            ->add('seizoen','seizoen_selector')
            //->add('user', 'entity', array('class'=>'Cyclear\GameBundle\Entity\User'));
        ;
    }

    public function getName()
    {
        return 'cyclear_gamebundle_ploegtype';
    }
}
