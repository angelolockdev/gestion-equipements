<?php

namespace App\Form;

use App\Entity\Employee;
use App\Repository\EquipmentRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipmentFilterType extends AbstractType
{
    private $equipmentRepository;

    public function __construct(EquipmentRepository $equipmentRepository)
    {
        $this->equipmentRepository = $equipmentRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $uniqueCategories = $this->equipmentRepository->findUniqueCategories();
        $categoryChoices = array_combine($uniqueCategories, $uniqueCategories);

        $builder
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'choices' => $categoryChoices,
                'placeholder' => 'Toutes les catégories',
                'required' => false,
            ])
            ->add('employee', EntityType::class, [
                'class' => Employee::class,
                'choice_label' => fn (?Employee $employee) => $employee ? $employee->getFirstName().' '.$employee->getLastName() : '',
                'label' => 'Employé assigné',
                'placeholder' => 'Tous les employés',
                'required' => false,
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Date de création (du)',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de création (au)',
                'widget' => 'single_text',
                'required' => false,
            ])
            // ->add('submit', SubmitType::class, [
            //     'label' => 'Filtrer',
            // ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
        ]);
    }

    // Un préfixe de bloc vide pour ne pas avoir de préfixe de nom de champ dans l'URL (ex: ?category=...)
    public function getBlockPrefix(): string
    {
        return '';
    }
}
