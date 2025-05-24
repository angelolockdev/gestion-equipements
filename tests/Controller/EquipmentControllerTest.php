<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\AppFixtures;
use App\Entity\Employee;
use App\Entity\Equipment;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EquipmentControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        // Nettoyage et chargement des fixtures
        $this->loadFixtures();
    }

    private function loadFixtures(): void
    {
        $purger = new ORMPurger($this->entityManager);
        $purger->purge();

        $executor = new ORMExecutor($this->entityManager, $purger);
        $executor->execute([new AppFixtures()]);
    }

    public function testNewEquipmentCreation(): void
    {
        $crawler = $this->client->request('GET', '/equipments/new');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Créer un nouvel équipement');

        $form = $crawler->selectButton('Sauvegarder')->form([
            'equipment[name]' => 'Souris Logitech',
            'equipment[category]' => 'Périphérique',
            'equipment[number]' => 'SN-MOUSE-456',
            'equipment[description]' => 'Souris sans fil ergonomique',
            // 'equipment[employee]' => null si pas d'employé initialement
        ]);

        $this->client->submit($form);

        $this->assertResponseRedirects('/equipments'); // Vérifie la redirection après la création
        $crawler = $this->client->followRedirect(); // Suivre la redirection

        $this->assertSelectorTextContains('h1', 'Liste des équipements');
        $this->assertSelectorTextContains('.table', 'Souris Logitech');

        // Vérifier que l'équipement est bien en base de données
        $equipment = $this->entityManager->getRepository(Equipment::class)->findOneBy(['number' => 'SN-MOUSE-456']);
        $this->assertNotNull($equipment);
        $this->assertEquals('Souris Logitech', $equipment->getName());
    }

    public function testEditEquipment(): void
    {
        // Créer un équipement en BDD pour le modifier
        $equipment = new Equipment();
        $equipment->setName('Old Name');
        $equipment->setCategory('Old Category');
        $equipment->setNumber('OLD-SN-123');
        $this->entityManager->persist($equipment);
        $this->entityManager->flush();
        $equipmentId = $equipment->getId();

        $crawler = $this->client->request('GET', '/equipments/'.$equipmentId.'/edit');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Modifier l\'équipement');

        $form = $crawler->selectButton('Mettre à jour')->form([
            'equipment[name]' => 'New Name',
            'equipment[category]' => 'New Category',
            'equipment[number]' => 'NEW-SN-456',
        ]);

        $this->client->submit($form);
        $this->assertResponseRedirects('/equipments');
        $this->client->followRedirect();

        // Vérifier que l'équipement a été mis à jour
        $updatedEquipment = $this->entityManager->getRepository(Equipment::class)->find($equipmentId);
        $this->assertEquals('New Name', $updatedEquipment->getName());
        $this->assertEquals('NEW-SN-456', $updatedEquipment->getNumber());
    }
}
