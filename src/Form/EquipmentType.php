<?php

namespace App\Form;

use App\Entity\Employee;
use App\Entity\Equipment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l\'équipement',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Ordinateur portable'],
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie',
                'required' => false,
                'attr' => ['placeholder' => 'Ex: Téléphone, Ordinateur'],
            ])
            ->add('number', TextType::class, [
                'label' => 'Numéro de série',
                'help' => 'Doit être unique.',
                'attr' => ['placeholder' => 'Ex: SN123456789'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'empty_data' => '', // Assure que la valeur par défaut est une chaîne vide
                'attr' => ['placeholder' => 'Détails supplémentaires sur l\'équipement...'],
            ])
            ->add('employee', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => fn (Employee $employee) => $employee->getFirstName().' '.$employee->getLastName().' ('.$employee->getEmail().')',
                'placeholder' => 'Aucun employé assigné',
                'required' => false,
                'label' => 'Assigner à un employé',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipment::class,
        ]);
    }
}
