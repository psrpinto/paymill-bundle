<?php

namespace Memeoirs\PaymillBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PaymillType extends AbstractType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('number' , 'text',   array('required' => false, 'label' => 'Card number'))
            ->add('expiry' , 'text',   array('required' => false, 'label' => 'Expires'))
            ->add('holder' , 'text',   array('required' => false, 'label' => 'Name on card'))
            ->add('cvc'    , 'text',   array('required' => false, 'label' => 'Card code'))
            ->add('token'  , 'hidden', array('required' => false))
        ;
    }

    public function getName()
    {
        return 'paymill';
    }
}