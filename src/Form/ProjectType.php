<?php
namespace App\Form;

use App\Entity\Client;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre du projet',
                'attr'  => ['placeholder' => 'Ex: Refonte site web']
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'En attente'  => 'en_attente',
                    'En cours'    => 'en_cours',
                    'Terminé'     => 'termine',
                    'Annulé'      => 'annule',
                ]
            ])
            ->add('budget', MoneyType::class, [
                'label'    => 'Budget',
                'currency' => 'EUR',
            ])
            ->add('client', EntityType::class, [
                'class'        => Client::class,
                'choice_label' => 'companyName',
                'label'        => 'Client',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}
