<?php

namespace Memeoirs\PaymillBundle\Form;

use Symfony\Component\Form\AbstractType,
    Symfony\Component\Form\FormBuilderInterface,
    Symfony\Component\Validator\Constraints\NotBlank;

class PaymillType extends AbstractType
{
    private $translator;

    public function __construct($translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $tokenNotBlank = new NotBlank(array(
            'message' => $this->translator->trans('default', array(), 'errors')
        ));

        $builder
            ->add('number' , 'text',   array('required' => false, 'label' => 'Card number'))
            ->add('expiry' , 'text',   array('required' => false, 'label' => 'Expires'))
            ->add('holder' , 'text',   array('required' => false, 'label' => 'Name on card'))
            ->add('cvc'    , 'text',   array('required' => false, 'label' => 'Card code'))
            ->add('token'  , 'hidden', array(
                'required' => false,
                'constraints' => array($tokenNotBlank)
            ))
        ;
    }

    public function getName()
    {
        return 'paymill';
    }
}