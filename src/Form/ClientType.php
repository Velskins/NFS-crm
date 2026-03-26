<?php
namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('companyName', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'attr'  => ['placeholder' => 'Ex: Acme Corp']
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom du contact',
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom du contact',
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
            ])
            ->add('phone', TextType::class, [
                'label'    => 'Téléphone',
                'required' => false,
            ])
            ->add('address', TextType::class, [
                'label'    => 'Adresse',
                'required' => false,
            ])
            ->add('postalCode', TextType::class, [
                'label'    => 'Code postal',
                'required' => false,
            ])
            ->add('city', TextType::class, [
                'label'    => 'Ville',
                'required' => false,
            ])
            ->add('businessSector', TextType::class, [
                'label'    => 'Secteur d\'activité',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label'    => 'Notes',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Client::class]);
    }
}
