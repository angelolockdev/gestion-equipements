<?php

namespace App\Tests\Functional;

use App\Entity\Equipment;
use App\Entity\Employee;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;

class EquipmentControllerTest extends WebTestCase
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

    private function createEquipment(string $number, ?string $name = null, ?string $category = null, ?Employee $employee = null): Equipment
    {
        $equipment = new Equipment();
        $equipment->setNumber($number);
        $equipment->setName($name);
        $equipment->setCategory($category);
        $equipment->setEmployee($employee);
        $this->entityManager->persist($equipment);
        $this->entityManager->flush();
        return $equipment;
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

    public function testIndexPageLoads(): void
    {
        $this->client->request('GET', '/equipments/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Liste des équipements');
    }

    public function testNewEquipmentForm(): void
    {
        $this->client->request('GET', '/equipments/new');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Créer un nouvel équipement');

        $this->client->submitForm('Enregistrer', [
            'equipment[name]' => 'Laptop',
            'equipment[category]' => 'Informatique',
            'equipment[number]' => 'SN_LAPTOP_001',
            'equipment[description]' => 'Dell XPS 15',
        ]);

        $this->assertResponseRedirects('/equipments/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('div.alert-success', 'L\'équipement a été créé avec succès.');
        $this->assertSelectorTextContains('td', 'Laptop');
    }

    public function testNewEquipmentFormWithInvalidData(): void
    {
        $this->client->request('GET', '/equipments/new');
        $this->client->submitForm('Enregistrer', [
            'equipment[name]' => 'Laptop',
            'equipment[category]' => 'Informatique',
            // Missing number (required)
            'equipment[description]' => 'Dell XPS 15',
        ]);

        $this->assertResponseIsSuccessful(); // Form validation error
        $this->assertSelectorTextContains('.invalid-feedback', 'Le numéro d\'équipement est obligatoire.');
    }

    public function testEditEquipment(): void
    {
        $equipment = $this->createEquipment('SN_OLD_001', 'Old Name');

        $this->client->request('GET', '/equipments/' . $equipment->getId() . '/edit');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier l\'équipement');

        $this->client->submitForm('Mettre à jour', [
            'equipment[name]' => 'Updated Name',
            'equipment[category]' => 'Updated Category',
            'equipment[number]' => 'SN_UPDATED_001', // Change number
            'equipment[description]' => 'Updated Description',
        ]);

        $this->assertResponseRedirects('/equipments/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('div.alert-success', 'L\'équipement a été mis à jour avec succès.');
        $this->assertSelectorTextContains('td', 'Updated Name');

        $updatedEquipment = $this->entityManager->find(Equipment::class, $equipment->getId());
        $this->assertSame('Updated Name', $updatedEquipment->getName());
        $this->assertSame('Updated Category', $updatedEquipment->getCategory());
        $this->assertSame('SN_UPDATED_001', $updatedEquipment->getNumber());
    }

    public function testSoftDeleteEquipment(): void
    {
        $equipment = $this->createEquipment('SN_DELETE_001', 'To Delete');
        $equipmentId = $equipment->getId();

        $crawler = $this->client->request('GET', '/equipments/');
        $this->assertSelectorTextContains('td', 'To Delete');

        $form = $crawler->filter('form[action$="/delete"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects('/equipments/');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('div.alert-success', 'L\'équipement a été supprimé (soft delete) avec succès.');
        $this->assertSelectorTextContains('td', 'To Delete (Supprimé)');

        // Verify in database
        $deletedEquipment = $this->entityManager->find(Equipment::class, $equipmentId);
        $this->assertNotNull($deletedEquipment->getDeletedAt());
    }
}