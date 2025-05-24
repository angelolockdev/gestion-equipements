<?php

namespace App\Controller;

use App\Entity\Equipment;
use App\Entity\Employee;
use App\Repository\EquipmentRepository;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    /**
     * Liste tous les équipements (non supprimés) via l'API.
     *
     * @param EquipmentRepository $equipmentRepository
     * @param SerializerInterface $serializer
     * @return Response
     */
    #[Route('/equipments', name: 'api_equipment_index', methods: ['GET'])]
    public function index(EquipmentRepository $equipmentRepository, SerializerInterface $serializer): Response
    {
        $equipments = $equipmentRepository->findNonDeleted(); // Récupère les équipements non supprimés

        // Sérialise les objets Equipment en JSON
        // Utilise les groupes 'equipment:read' pour contrôler les champs exposés
        $jsonContent = $serializer->serialize($equipments, 'json', ['groups' => 'equipment:read']);

        return new Response($jsonContent, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }

    /**
     * Crée un nouvel équipement via l'API.
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     * @param ValidatorInterface $validator
     * @param EmployeeRepository $employeeRepository
     * @return Response
     */
    #[Route('/equipments', name: 'api_equipment_new', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        EmployeeRepository $employeeRepository
    ): Response {
        // Désérialise le contenu JSON de la requête en un objet Equipment
        // Utilise les groupes 'equipment:write' pour contrôler les champs acceptés en écriture
        $equipment = $serializer->deserialize($request->getContent(), Equipment::class, 'json', ['groups' => 'equipment:write']);

        // Si un ID d'employé est fourni dans le JSON, l'associer
        $data = json_decode($request->getContent(), true);
        if (isset($data['employee']['id'])) {
            $employee = $employeeRepository->find($data['employee']['id']);
            if ($employee) {
                $equipment->setEmployee($employee);
            } else {
                // Gérer le cas où l'employé n'est pas trouvé
                return new Response(
                    $serializer->serialize(['message' => 'Employé non trouvé pour l\'ID fourni.'], 'json'),
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    ['Content-Type' => 'application/json']
                );
            }
        }

        $errors = $validator->validate($equipment);

        if (count($errors) > 0) {
            // S'il y a des erreurs de validation, les sérialiser et les retourner
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }
            return new Response(
                $serializer->serialize(['errors' => $errorMessages], 'json'),
                Response::HTTP_BAD_REQUEST,
                ['Content-Type' => 'application/json']
            );
        }

        // Persiste et flush l'équipement
        $entityManager->persist($equipment);
        $entityManager->flush();

        // Retourne l'équipement créé avec un statut 201 Created
        $jsonContent = $serializer->serialize($equipment, 'json', ['groups' => 'equipment:read']);
        return new Response($jsonContent, Response::HTTP_CREATED, ['Content-Type' => 'application/json']);
    }
}