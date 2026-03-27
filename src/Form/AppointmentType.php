<?php

namespace App\Form;

use App\Entity\Appointment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AppointmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('scheduleAt', DateTimeType::class, [
                'label' => 'Date et heure du rendez-vous',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note (optionnel)',
                'required' => false,
                'attr' => ['placeholder' => 'Objet du rendez-vous, remarques...', 'rows' => 3]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Appointment::class]);
    }
}
