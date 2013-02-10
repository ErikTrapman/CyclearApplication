<?php

namespace Cyclear\GameBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RennerType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('naam')
            ->add('cqranking_id', null, array('required' => true, 'label' => 'CQ-id'))
            ->add('country', 'entity', array(
                'class' => 'CyclearGameBundle:Country',
                'query_builder' => function(\Doctrine\ORM\EntityRepository $e) {
                    return $e->createQueryBuilder('c')->orderBy('c.name');
                }))
        ;
    }

    public function getName()
    {
        return 'cyclear_gamebundle_rennertype';
    }
}
