<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Form\EquipmentFilterType;
use App\Form\EquipmentType;
use App\Repository\EmployeeRepository;
use App\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/equipments')]
class EquipmentController extends AbstractController
{
    #[Route('/', name: 'app_equipment_index', methods: ['GET'])]
    public function index(
        Request $request,
        EquipmentRepository $equipmentRepository,
        EmployeeRepository $employeeRepository,
    ): Response {
        $filterForm = $this->createForm(EquipmentFilterType::class);
        $filterForm->handleRequest($request);

        // Initialisation de la requête de base
        $queryBuilder = $equipmentRepository->createQueryBuilder('e')
            ->leftJoin('e.employee', 'emp')
            ->addSelect('emp');

        // Gestion du soft delete
        $queryBuilder->andWhere('e.deletedAt IS NULL');

        if ($filterForm->isSubmitted() && $filterForm->isValid()) {
            $data = $filterForm->getData();

            if ($data['category']) {
                $queryBuilder->andWhere('e.category = :category')
                    ->setParameter('category', $data['category']);
            }
            if ($data['employee']) {
                $queryBuilder->andWhere('e.employee = :employee')
                    ->setParameter('employee', $data['employee']);
            }
            if ($data['startDate']) {
                $queryBuilder->andWhere('e.createdAt >= :startDate')
                    ->setParameter('startDate', $data['startDate']);
            }
            if ($data['endDate']) {
                $queryBuilder->andWhere('e.createdAt <= :endDate')
                    ->setParameter('endDate', $data['endDate']);
            }
        }

        return $this->render('equipment/index.html.twig', [
            'equipments' => $queryBuilder->getQuery()->getResult(),
            'filterForm' => $filterForm->createView(),
        ]);
    }

    #[Route('/new', name: 'app_equipment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, EmployeeRepository $employeeRepository): Response
    {
        $equipment = new Equipment();
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$equipment->getCreatedAt()) {
                $equipment->setCreatedAt(new \DateTimeImmutable());
            }
            $equipment->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($equipment);
            $entityManager->flush();

            $this->addFlash('success', 'L\'équipement a été créé avec succès.');

            return $this->redirectToRoute('app_equipment_index');
        }

        $employees = $employeeRepository->findAll();

        return $this->render('equipment/new.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
            'employees' => $employees,
        ]);
    }

    #[Route('/{id}', name: 'app_equipment_show', methods: ['GET'])]
    public function show(Equipment $equipment): Response
    {
        return $this->render('equipment/show.html.twig', [
            'equipment' => $equipment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_equipment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipment $equipment, EntityManagerInterface $entityManager, EmployeeRepository $employeeRepository): Response
    {
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $equipment->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'L\'équipement a été mis à jour avec succès.');

            return $this->redirectToRoute('app_equipment_index');
        }

        // Passage de la liste des employés à la vue
        $employees = $employeeRepository->findAll();

        return $this->render('equipment/edit.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
            'employees' => $employees,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_equipment_delete', methods: ['POST'])]
    public function delete(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$equipment->getId(), $request->request->get('_token'))) {
            $equipment->softDelete(); // Soft delete
            $entityManager->flush();
            $this->addFlash('success', 'L\'équipement a été supprimé (soft delete) avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_equipment_index');
    }

    #[Route('/export/{format}', name: 'app_equipment_export', methods: ['GET'])]
    public function export(string $format, EquipmentRepository $equipmentRepository): Response
    {
        $equipments = $equipmentRepository->findNonDeleted(); // Exporter seulement les non supprimés

        if ('csv' === $format) {
            $csvContent = $equipmentRepository->exportToCsv($equipments);
            $response = new Response($csvContent);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="equipments.csv"');

            return $response;
        } elseif ('json' === $format) {
            $jsonContent = $equipmentRepository->exportToJson($equipments);
            $response = new Response($jsonContent);
            $response->headers->set('Content-Type', 'application/json');
            $response->headers->set('Content-Disposition', 'attachment; filename="equipments.json"');

            return $response;
        }

        throw $this->createNotFoundException('Format d\'exportation non supporté.');
    }

    #[Route('/assign/{equipmentId}/{employeeId}', name: 'app_equipment_assign', methods: ['POST'])]
    public function assign(
        string $equipmentId,
        string $employeeId,
        EquipmentRepository $equipmentRepository,
        EmployeeRepository $employeeRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $equipment = $equipmentRepository->find($equipmentId);
        $employee = $employeeRepository->find($employeeId);

        if (!$equipment || !$employee) {
            $this->addFlash('error', 'Équipement ou employé introuvable.');

            return $this->redirectToRoute('app_equipment_index');
        }

        $equipment->setEmployee($employee);
        $entityManager->flush();
        $this->addFlash('success', 'Équipement assigné avec succès.');

        return $this->redirectToRoute('app_equipment_index');
    }

    #[Route('/unassign/{equipmentId}', name: 'app_equipment_unassign', methods: ['POST'])]
    public function unassign(
        string $equipmentId,
        EquipmentRepository $equipmentRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $equipment = $equipmentRepository->find($equipmentId);

        if (!$equipment) {
            $this->addFlash('error', 'Équipement introuvable.');

            return $this->redirectToRoute('app_equipment_index');
        }

        $equipment->setEmployee(null);
        $entityManager->flush();
        $this->addFlash('success', 'Équipement désassigné avec succès.');

        return $this->redirectToRoute('app_equipment_index');
    }
}
