<?php

namespace App\Form;

use App\Entity\Employee;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmployeeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => ['placeholder' => 'Ex: Jean'],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom de famille',
                'attr' => ['placeholder' => 'Ex: Dupont'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'help' => 'Doit être unique et au format email.',
                'attr' => ['placeholder' => 'Ex: jean.dupont@manatime.com'],
            ])
            ->add('hiredAt', DateType::class, [
                'label' => 'Date d\'embauche',
                'widget' => 'single_text',
                'html5' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Employee::class,
        ]);
    }
}
