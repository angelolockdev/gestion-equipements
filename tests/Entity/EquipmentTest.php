<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Equipment;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;

class EquipmentTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $equipment = new Equipment();
        $now = new \DateTimeImmutable();

        $equipment->setName('Laptop Dell XPS');
        $equipment->setCategory('Ordinateur');
        $equipment->setNumber('SN-ABC-123');
        $equipment->setDescription('Portable haute performance');
        $equipment->setCreatedAt($now);
        $equipment->setUpdatedAt($now);

        $this->assertEquals('Laptop Dell XPS', $equipment->getName());
        $this->assertEquals('Ordinateur', $equipment->getCategory());
        $this->assertEquals('SN-ABC-123', $equipment->getNumber());
        $this->assertEquals('Portable haute performance', $equipment->getDescription());
        $this->assertEquals($now, $equipment->getCreatedAt());
        $this->assertEquals($now, $equipment->getUpdatedAt());
        $this->assertNull($equipment->getDeletedAt());
    }

    public function testValidation(): void
    {
        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();

        // Test with valid data
        $equipment = new Equipment();
        $equipment->setNumber('VALID-SN-123');
        $violations = $validator->validate($equipment);
        $this->assertCount(0, $violations);

        $equipment = new Equipment();
        $equipment->setNumber(null);
        $violations = $validator->validate($equipment);
        $this->assertGreaterThan(0, $violations);
        $this->assertEquals('Le numéro de série est obligatoire.', $violations[0]->getMessage());
    }

    public function testSoftDelete(): void
    {
        $equipment = new Equipment();
        $this->assertNull($equipment->getDeletedAt());

        $equipment->setDeletedAt(new \DateTimeImmutable());
        $this->assertNotNull($equipment->getDeletedAt());

        $equipment->setDeletedAt(null);
        $this->assertNull($equipment->getDeletedAt());
    }
}