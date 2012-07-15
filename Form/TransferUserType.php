<?php

namespace Cyclear\GameBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Cyclear\GameBundle\Validator\Constraints as CyclearAssert;

/**
 * @CyclearAssert\UserTransfer
 * 
 */
class TransferUserType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
                ->add('renner_in', 'renner_selector', array('attr'=> array('size'=>40)))
        ;
    }

    public function getName() {
        return 'cyclear_gamebundle_transferusertype';
    }

}
