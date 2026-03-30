<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Quote;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, [
                'label' => 'Objet du devis',
                'attr' => [
                    'placeholder' => 'Ex: Création site vitrine',
                    'class' => 'form-control',
                ],
                'required' => false,
            ])
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'companyName',
                'label' => 'Client',
                'attr' => ['class' => 'form-control'],
                'placeholder' => 'Sélectionner un client',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => Quote::getStatuses(),
                'attr' => ['class' => 'form-control'],
            ])
            ->add('validUntil', DateType::class, [
                'label' => 'Valide jusqu\'au',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes / Conditions',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Conditions de paiement, délais de réalisation...',
                    'class' => 'form-control',
                    'rows' => 4,
                ],
            ])
            ->add('lines', CollectionType::class, [
                'entry_type' => QuoteLineType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Quote::class,
        ]);
    }
}
