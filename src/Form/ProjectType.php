<?php
namespace App\Form;

use App\Entity\Client;
use App\Entity\Project;
use App\Form\TaskType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

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
                    'En cours' => 'en_cours',
                    'Livré'    => 'livre',
                    'Payé'     => 'paye',
                ]
            ])
            ->add('budget', MoneyType::class, [
                'label'    => 'Budget',
                'currency' => 'EUR',
            ])
            ->add('deadline', DateType::class, [
                'label'    => 'Date limite',
                'widget'   => 'single_text',
                'required' => false,
                'input'    => 'datetime_immutable',
            ])
            ->add('client', EntityType::class, [
                'class'        => Client::class,
                'choice_label' => 'companyName',
                'label'        => 'Client',
            ])
            ->add('tasks', CollectionType::class, [
                'entry_type' => TaskType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
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
