<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Form\EquipmentType;
use App\Repository\EmployeeRepository;
use App\Repository\EquipmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/equipment')]
class EquipmentController extends AbstractController
{
    #[Route('/', name: 'app_equipment_index', methods: ['GET'])]
    public function index(
        Request $request,
        EquipmentRepository $equipmentRepository,
        EmployeeRepository $employeeRepository,
    ): Response {
        $category = $request->query->get('category');
        $employeeId = $request->query->get('employee');
        $dateFrom = $request->query->get('dateFrom') ? new \DateTimeImmutable($request->query->get('dateFrom')) : null;
        $dateTo = $request->query->get('dateTo') ? new \DateTimeImmutable($request->query->get('dateTo')) : null;

        $employee = null;
        if ($employeeId) {
            $employee = $employeeRepository->find($employeeId);
        }

        $equipments = $equipmentRepository->findNonDeleted($category, $employee, $dateFrom, $dateTo);
        $allEmployees = $employeeRepository->findAll(); // Pour le filtre par employé
        $availableCategories = array_unique(array_map(fn ($e) => $e->getCategory(), $equipmentRepository->findAll())); // Pour le filtre par catégorie

        return $this->render('equipment/index.html.twig', [
            'equipments' => $equipments,
            'allEmployees' => $allEmployees,
            'availableCategories' => array_filter($availableCategories), // Supprime les null
            'filters' => [
                'category' => $category,
                'employee' => $employeeId,
                'dateFrom' => $request->query->get('dateFrom'),
                'dateTo' => $request->query->get('dateTo'),
            ],
        ]);
    }

    #[Route('/new', name: 'app_equipment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $equipment = new Equipment();
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($equipment);
            $entityManager->flush();

            $this->addFlash('success', 'L\'équipement a été créé avec succès.');

            return $this->redirectToRoute('app_equipment_index');
        }

        return $this->render('equipment/new.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
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
    public function edit(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EquipmentType::class, $equipment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'équipement a été mis à jour avec succès.');

            return $this->redirectToRoute('app_equipment_index');
        }

        return $this->render('equipment/edit.html.twig', [
            'equipment' => $equipment,
            'form' => $form,
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

    #[Route('/{id}/restore', name: 'app_equipment_restore', methods: ['POST'])]
    public function restore(Request $request, Equipment $equipment, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('restore'.$equipment->getId(), $request->request->get('_token'))) {
            $equipment->restore(); // Restore
            $entityManager->flush();
            $this->addFlash('success', 'L\'équipement a été restauré avec succès.');
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
