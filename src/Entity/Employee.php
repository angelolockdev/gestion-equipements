<?php
namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
class Employee
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de famille est obligatoire.')]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'Le format de l\'email est invalide.')]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotBlank(message: 'La date d\'embauche est obligatoire.')]
    #[Assert\LessThanOrEqual('today', message: 'La date d\'embauche ne peut pas être dans le futur.')]
    private ?\DateTimeImmutable $hiredAt = null;

    #[ORM\OneToMany(mappedBy: 'employee', targetEntity: Equipment::class)]
    private Collection $equipment;

    public function __construct()
    {
        $this->equipment = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getHiredAt(): ?\DateTimeImmutable
    {
        return $this->hiredAt;
    }

    public function setHiredAt(\DateTimeImmutable $hiredAt): static
    {
        $this->hiredAt = $hiredAt;

        return $this;
    }

    /**
     * @return Collection<int, Equipment>
     */
    public function getEquipment(): Collection
    {
        return $this->equipment;
    }

    public function addEquipment(Equipment $equipment): static
    {
        if (!$this->equipment->contains($equipment)) {
            $this->equipment->add($equipment);
            $equipment->setEmployee($this);
        }

        return $this;
    }

    public function removeEquipment(Equipment $equipment): static
    {
        if ($this->equipment->removeElement($equipment)) {
            if ($equipment->getEmployee() === $this) {
                $equipment->setEmployee(null);
            }
        }

        return $this;
    }
}