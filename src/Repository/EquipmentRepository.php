<?php

namespace App\Repository;

use App\Entity\Employee;
use App\Entity\Equipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipment>
 *
 * @method Equipment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Equipment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Equipment[]    findAll()
 * @method Equipment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipment::class);
    }

    /**
     * Retourne une liste de toutes les catégories uniques d'équipements non supprimés.
     * @return array
     */
    public function findUniqueCategories(): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('DISTINCT e.category')
            ->where('e.deletedAt IS NULL') // Exclure les équipements supprimés
            ->andWhere('e.category IS NOT NULL') // Exclure les catégories nulles
            ->orderBy('e.category', 'ASC');

        return array_column($qb->getQuery()->getScalarResult(), 'category');
    }

    /**
     * Récupère tous les équipements non supprimés, avec des options de filtrage.
     *
     * @return Equipment[]
     */
    public function findNonDeleted(
        ?string $category = null,
        ?Employee $employee = null,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
    ): array {
        $qb = $this->createQueryBuilder('e')
            ->where('e.deletedAt IS NULL');

        if ($category) {
            $qb->andWhere('e.category = :category')
               ->setParameter('category', $category);
        }

        if ($employee) {
            $qb->andWhere('e.employee = :employee')
               ->setParameter('employee', $employee);
        }

        if ($dateFrom) {
            $qb->andWhere('e.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo) {
            $qb->andWhere('e.createdAt <= :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Exporte les équipements non supprimés au format CSV.
     */
    public function exportToCsv(array $equipments): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ID', 'Nom', 'Catégorie', 'Numéro', 'Description', 'Créé le', 'Assigné à']);

        foreach ($equipments as $equipment) {
            $employeeName = $equipment->getEmployee() ?
                $equipment->getEmployee()->getFirstName().' '.$equipment->getEmployee()->getLastName() :
                'Non assigné';
            fputcsv($handle, [
                $equipment->getId()->toRfc4122(),
                $equipment->getName(),
                $equipment->getCategory(),
                $equipment->getNumber(),
                $equipment->getDescription(),
                $equipment->getCreatedAt()?->format('Y-m-d H:i:s'),
                $employeeName,
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Exporte les équipements non supprimés au format JSON.
     */
    public function exportToJson(array $equipments): string
    {
        $data = [];
        foreach ($equipments as $equipment) {
            $data[] = [
                'id' => $equipment->getId()?->toRfc4122(),
                'name' => $equipment->getName(),
                'category' => $equipment->getCategory(),
                'number' => $equipment->getNumber(),
                'description' => $equipment->getDescription(),
                'createdAt' => $equipment->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $equipment->getUpdatedAt()?->format('Y-m-d H:i:s'),
                'deletedAt' => $equipment->getDeletedAt()?->format('Y-m-d H:i:s'),
                'employee' => $equipment->getEmployee() ? [
                    'id' => $equipment->getEmployee()->getId()?->toRfc4122(),
                    'firstName' => $equipment->getEmployee()->getFirstName(),
                    'lastName' => $equipment->getEmployee()->getLastName(),
                    'email' => $equipment->getEmployee()->getEmail(),
                ] : null,
            ];
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
