<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Security\Authenticated;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use App\Entity\Hive;
use App\Entity\Intervention;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


#[Authenticated]
final class InterventionController extends AbstractController
{
    #[Route('/api/intervention/{id}', name: 'app_api_create_intervention', methods: ['POST'])]
    public function addIntervention (
        int $id,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository, 
        Request $request,
        LoggerInterface $logger
    ): JsonResponse
    {
        try {
            $email = $request->get('jwt_email');
            if (empty($email)) {
                return new JsonResponse(
                    [
                        'success' => false,
                        'message' => 'Unauthorized'
                    ],
                    Response::HTTP_UNAUTHORIZED);
            }

            $user = $userRepository->findOneByEmail($email);
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User was not found'
                ],Response::HTTP_UNAUTHORIZED);
            }

            $content = json_decode($request->getContent(), true);
            if(!isset($content['note'])) {
                return new JsonResponse([
                    'success'=> false,
                    'message'=> 'Bad request, missing note element'
                ],Response::HTTP_BAD_REQUEST);
            }

            $note = $content['note'];
            $hive = $entityManager->getRepository(Hive::class)->find($id);
            if (!$hive) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Hive not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            if ($hive->getOwner()->getId() !== $user->getId()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Forbidden: You do not own this hive'
                ], Reponse::HTTP_FORBIDDEN);
            }

            $intervention = new Intervention();
            $intervention->setNote($note);
            $hive->addIntervention($intervention);

            $entityManager->persist($intervention);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Intervention created succefully',
                'intervention' => [
                    'id' => $intervention->getId(),
                    'note' => $intervention->getNote(),
                    'createAt' => $intervention->getCreatedAt()->format('c'),
                    'hiveId' => $hive->getId()
                ] 
            ], 
             Response::HTTP_CREATED);
        }
        catch(\Exception $e) {
            return new JsonResponse(
            [
                'success' => false,
                'message' => 'Internal Server Error'
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/intervention/{id}', name: 'app_api_get_intervention', methods: ['GET'])]
    public function getInterventions(
        int $id,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository, 
        Request $request,
        LoggerInterface $logger
    ) :JsonResponse
    {
        try {
            $email = $request->get('jwt_email');
            if (empty($email)) {
                return new JsonResponse(
                    [
                        'success' => false,
                        'message' => 'Unauthorized'
                    ],
                    Response::HTTP_UNAUTHORIZED);
            }

            $user = $userRepository->findOneByEmail($email);
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'User was not found'
                ],Response::HTTP_UNAUTHORIZED);
            }

            $hive = $entityManager->getRepository(Hive::class)->find($id);
            if (!$hive) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Hive not found'
                ], Response::HTTP_NOT_FOUND);
            }
            
            if ($hive->getOwner()->getId() !== $user->getId()) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Forbidden: You do not own this hive'
                ], Reponse::HTTP_FORBIDDEN);
            }

            $intervention = $hive->getIntervention();
            $interventions = [];
            foreach($intervention as $i) {
                $interventions[] = [
                    'id' => $i->getId(),
                    'note' => $i->getNote(),
                    'createdAt' => $i->getCreatedAt(),
                    'hiveId' => $i->getHive()->getId()
                ];
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Intervention created succefully',
                'interventions' => $interventions
            ], 
             Response::HTTP_OK);
        }
        catch(\Exception $e) {
            return new JsonResponse(
            [
                'success' => false,
                'message' => 'Internal Server Error'
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
