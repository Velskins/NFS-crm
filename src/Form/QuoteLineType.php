<?php

namespace App\Form;

use App\Entity\QuoteLine;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteLineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('description', TextType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Description de la prestation',
                    'class' => 'form-control',
                ],
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'Quantité',
                'attr' => [
                    'placeholder' => '1',
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => '0.01',
                ],
                'html5' => true,
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'Prix unitaire',
                'attr' => [
                    'placeholder' => '0.00',
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => '0.01',
                ],
                'html5' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuoteLine::class,
        ]);
    }
}
