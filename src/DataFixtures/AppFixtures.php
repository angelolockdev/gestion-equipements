<?php

namespace App\DataFixtures;

use App\Entity\Employee;
use App\Entity\Equipment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Uid\Uuid;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            'Ordinateur',
            'Moniteur',
            'Périphérique',
            'Téléphone',
            'Casque audio',
            'Accessoire',
            'Projecteur',
            'Tablette',
        ];

        // Créer 5 employés
        $employees = [];
        for ($i = 0; $i < 5; ++$i) {
            $employee = new Employee();
            $employee->setFirstName('Employe'.$i);
            $employee->setLastName('Nom'.$i);
            $employee->setEmail('employe'.$i.'@example.com');
            $employee->setHiredAt(new \DateTimeImmutable(sprintf('-%d years', $i + 1)));
            $manager->persist($employee);
            $employees[] = $employee;
        }

        // Créer 10 équipements
        for ($i = 0; $i < 5; ++$i) {
            $equipment = new Equipment();
            $equipment->setName('Article '.$i);
            $equipment->setCategory(0 === $i % 2 ? 'Ordinateur' : 'Téléphone');
            $equipment->setNumber('SN-'.Uuid::v4()->toRfc4122());
            $equipment->setDescription('Description de l\'article '.$i);
            $equipment->setCreatedAt(new \DateTimeImmutable());

            if ($i < 5) {
                $equipment->setEmployee($employees[array_rand($employees)]);
            }

            $manager->persist($equipment);
        }

        $manager->flush();
    }
}
