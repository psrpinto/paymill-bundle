<?php

namespace Fm\PaymentPaymillBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PaymillType extends AbstractType
{
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('number' , 'text',   array('required' => false))
            ->add('expires', 'text',   array('required' => false))
            ->add('holder' , 'text',   array('required' => false))
            ->add('code'   , 'text',   array('required' => false))
            ->add('token'  , 'hidden', array('required' => false))
        ;
    }

    public function getName()
    {
        return 'paymill';
    }
}