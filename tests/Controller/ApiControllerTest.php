<?php

namespace App\Tests\Functional;

use App\Entity\Employee;
use App\Entity\Equipment;
use Symfony\Component\Uid\Uuid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiControllerTest extends WebTestCase
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

    private function createEquipment(string $number, ?string $name = null, ?Employee $employee = null): Equipment
    {
        $equipment = new Equipment();
        $equipment->setNumber($number);
        $equipment->setName($name);
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

    public function testGetEquipments(): void
    {
        $this->createEquipment('SN001', 'Laptop A');
        $this->createEquipment('SN002', 'Monitor B');
        $deletedEquipment = $this->createEquipment('SN003', 'Deleted Item');
        $deletedEquipment->softDelete();
        $this->entityManager->flush();

        $this->client->request('GET', '/api/equipments');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($responseData);
        $this->assertCount(2, $responseData); // Only non-deleted equipments
        $this->assertEquals('Laptop A', $responseData[0]['name']);
        $this->assertEquals('Monitor B', $responseData[1]['name']);
        
        // Assert deleted item is not present
        $this->assertFalse(in_array('SN003', array_column($responseData, 'number')));
    }

    public function testCreateNewEquipment(): void
    {
        $requestData = [
            'name' => 'New Tablet',
            'category' => 'Mobile',
            'number' => 'TBL_999',
            'description' => 'A shiny new tablet',
        ];

        $this->client->request('POST', '/api/equipments', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($requestData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('New Tablet', $responseData['name']);
        $this->assertEquals('TBL_999', $responseData['number']);

        // Verify it's in the database
        $equipment = $this->entityManager->getRepository(Equipment::class)->findOneBy(['number' => 'TBL_999']);
        $this->assertNotNull($equipment);
        $this->assertEquals('New Tablet', $equipment->getName());
    }

    public function testCreateNewEquipmentWithEmployee(): void
    {
        $employee = $this->createEmployee('api.user@example.com', 'API', 'User');

        $requestData = [
            'name' => 'Smartphone',
            'category' => 'Mobile',
            'number' => 'SPH_777',
            'employee' => ['id' => $employee->getId()->toRfc4122()], // Pass employee ID
        ];

        $this->client->request('POST', '/api/equipments', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($requestData));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals('Smartphone', $responseData['name']);
        $this->assertArrayHasKey('employee', $responseData);
        $this->assertEquals($employee->getId()->toRfc4122(), $responseData['employee']['id']);

        // Verify association in database
        $equipment = $this->entityManager->getRepository(Equipment::class)->findOneBy(['number' => 'SPH_777']);
        $this->assertNotNull($equipment);
        $this->assertNotNull($equipment->getEmployee());
        $this->assertEquals($employee->getId(), $equipment->getEmployee()->getId());
    } 
}