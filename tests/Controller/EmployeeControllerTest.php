<?php

namespace App\Tests\Functional;

use App\Entity\Employee;
use App\Entity\Equipment;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;

class EmployeeControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        // Clean up database before each test
        $this->clearDatabase();
    }

    protected function tearDown(): void
    {
        $this->clearDatabase();
        parent::tearDown();
    }

    private function clearDatabase(): void
    {
        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE equipment;');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE employee;');
        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function createEmployee(string $email, string $firstName, string $lastName): Employee
    {
        $employee = new Employee();
        $employee->setEmail($email);
        $employee->setFirstName($firstName);
        $employee->setLastName($lastName);
        $employee->setHiredAt(new \DateTimeImmutable());
        $this->entityManager->persist($employee);
        $this->entityManager->flush();
        return $employee;
    }

    private function createEquipment(string $number, ?Employee $employee = null): Equipment
    {
        $equipment = new Equipment();
        $equipment->setNumber($number);
        $equipment->setEmployee($employee);
        $this->entityManager->persist($equipment);
        $this->entityManager->flush();
        return $equipment;
    }


    public function testIndexPageLoads(): void
    {
        $this->client->request('GET', '/employees/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des employés');
    }

    public function testNewEmployeeForm(): void
    {
        $this->client->request('GET', '/employees/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Créer un nouvel employé');

        $this->client->submitForm('Enregistrer', [
            'employee[firstName]' => 'Jane',
            'employee[lastName]' => 'Smith',
            'employee[email]' => 'jane.smith@example.com',
            'employee[hiredAt]' => '2023-01-01',
        ]);

        $this->assertResponseRedirects('/employees/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('div.alert-success', 'L\'employé a été créé avec succès.');
        $this->assertSelectorTextContains('td', 'Jane Smith');
    }

    public function testNewEmployeeFormWithInvalidEmail(): void
    {
        $this->client->request('GET', '/employees/new');
        $this->client->submitForm('Enregistrer', [
            'employee[firstName]' => 'Invalid',
            'employee[lastName]' => 'Email',
            'employee[email]' => 'invalid-email', // Invalid email format
            'employee[hiredAt]' => '2023-01-01',
        ]);

        $this->assertSelectorTextContains('body', 'Le format de l&#039;email est invalide.');
        $this->assertSelectorTextContains('.invalid-feedback', 'Le format de l\'email est invalide.');
    }

    public function testEditEmployee(): void
    {
        $employee = $this->createEmployee('edit.test@example.com', 'Edit', 'Test');

        $this->client->request('GET', '/employees/' . $employee->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier l\'employé');

        $this->client->submitForm('Mettre à jour', [
            'employee[firstName]' => 'Edited',
            'employee[lastName]' => 'Name',
            'employee[email]' => 'edited.test@example.com',
            'employee[hiredAt]' => '2023-02-01',
        ]);

        $this->assertResponseRedirects('/employees/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('div.alert-success', 'L\'employé a été mis à jour avec succès.');
        $this->assertSelectorTextContains('td', 'Edited Name');

        $updatedEmployee = $this->entityManager->find(Employee::class, $employee->getId());
        $this->assertSame('Edited', $updatedEmployee->getFirstName());
        $this->assertSame('edited.test@example.com', $updatedEmployee->getEmail());
    }

    public function testDeleteEmployee(): void
    {
        $employee = $this->createEmployee('delete.test@example.com', 'Delete', 'Test');
        $equipment1 = $this->createEquipment('EQ_001', $employee);
        $equipment2 = $this->createEquipment('EQ_002', $employee);

        // Verify equipments are assigned
        $this->assertNotNull($this->entityManager->find(Equipment::class, $equipment1->getId())->getEmployee());
        $this->assertNotNull($this->entityManager->find(Equipment::class, $equipment2->getId())->getEmployee());

        $crawler = $this->client->request('GET', '/employees/');
        $form = $crawler->filter('form[action$="/delete"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/employees/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('div.alert-success', 'L\'employé a été supprimé avec succès. Ses équipements ont été désassignés.');
        $this->assertSelectorTextNotContains('td', 'Delete Test'); // Employee should be gone from list

        // Verify employee is deleted from database
        $deletedEmployee = $this->entityManager->find(Employee::class, $employee->getId());
        $this->assertNull($deletedEmployee);

        // Verify equipments are unassigned
        $unassignedEquipment1 = $this->entityManager->find(Equipment::class, $equipment1->getId());
        $unassignedEquipment2 = $this->entityManager->find(Equipment::class, $equipment2->getId());
        $this->assertNull($unassignedEquipment1->getEmployee());
        $this->assertNull($unassignedEquipment2->getEmployee());
    }
}