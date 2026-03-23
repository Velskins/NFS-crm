<?php
namespace App\Form;

use App\Entity\PaymentSchedule;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PaymentScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('amount', MoneyType::class, [
                'label'    => 'Montant',
                'currency' => 'EUR',
            ])
            ->add('dueDate', DateType::class, [
                'label'  => 'Date d\'échéance',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
            ])
            ->add('paidAt', DateType::class, [
                'label'    => 'Date de paiement réel',
                'widget'   => 'single_text',
                'required' => false,
                'input'    => 'datetime_immutable',
            ])
            ->add('status', ChoiceType::class, [
                'label'   => 'Statut',
                'choices' => [
                    'En attente' => 'en_attente',
                    'Payé'       => 'paye',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PaymentSchedule::class,
        ]);
    }
}
