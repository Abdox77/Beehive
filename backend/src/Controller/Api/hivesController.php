<?php

namespace App\Controller\Api;

use App\Entity\Hive;
use App\Entity\Harvest;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\Common\Util\Debug;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class hivesController extends AbstractController
{
    #[Route('/api/hive', name: 'api_hives_create', methods: ['POST'])]
    public function createHive (
        EntityManagerInterface $entityManager, 
        UserRepository $userRepository, 
        Request $request,
        LoggerInterface $logger
    ): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(
                    ['error' => 'Unauthorized'],
                    Response::HTTP_UNAUTHORIZED
                );
            }
            
            $content = json_decode($request->getContent(), true);

            if (!isset($content['name']) || !isset($content['lng']) || !isset($content['lat']))
            {
                return new JsonResponse([
                        'success' => false,
                        'message' => 'invalid request'
                    ],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $name = $content['name'];
            $lng = $content['lng'];
            $lat = $content['lat'];
            
            if (empty($name) || $lng === null || $lat === null)
            {
                return new JsonResponse(
                    ['error' => 'Bad Request'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            $lng = floatval($lng);
            $lat = floatval($lat);
            if ($lng < -180 || $lng > 180 || $lat > 90 || $lat < -90) {
                return new JsonResponse(
                    ['error' => 'Bad Request'],
                    Response::HTTP_BAD_REQUEST
                );
            }


            $hive = new Hive();
            $hive->setName($name);
            $hive->setOwner($user);
            $hive->setLng($lng);
            $hive->setLat($lat);
            $entityManager->persist($hive);
            $entityManager->flush();
            $user->addHive($hive);
            return new JsonResponse([
                'success' => 'The hive was created successfully',
                'hive' => [
                   'id' => $hive->getId(),
                   'name' => $hive->getName(),
                   'lat' => $hive->getLat(),
                   'lng' => $hive->getLng(),
                   'owner' => $user->getEmail() 
                ]
            ],Response::HTTP_CREATED);
        }
        catch (\Exception $e) {
            return new JsonResponse(
            [
                'success' => false,
                'message' => 'Internal Server Error'
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/api/hive', name: 'api_hives_list', methods: ['GET'])]
    public function getHives(
        UserRepository $userRepository, 
        Request $request,
        LoggerInterface $logger
    ): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $hives = $user->getHives();
            $hivesData = [];
            foreach ($hives as $hive) {
                $hivesData[] = [
                    'id' => $hive->getId(),
                    'name' => $hive->getName(),
                    'lat' => $hive->getLat(),
                    'lng' => $hive->getLng()
                ];
            }

            return new JsonResponse(
                [
                    'success' => 'true',
                    'hives' => $hivesData
                ],Response::HTTP_OK);
        }
        catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message'=> 'Internal Server Error' . ' ' . $e->getMessage()
            ],Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/hive/{id}', name: 'api_hives_delete', methods: ['DELETE'])]
    public function deleteHive(
        int $id,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository, 
        Request $request,
        LoggerInterface $logger
    ): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(
                    ['error' => 'Unauthorized'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            $hiveRepository = $entityManager->getRepository(Hive::class);
            if ($hiveRepository->deleteUserHive($user, $id)) {
                return new JsonResponse(
                    [
                        'success' => true,
                        'message' => 'Hive deleted successfully'
                    ],
                    Response::HTTP_OK
                );
            }
            return new JsonResponse([
                'success'=> false,
                'message'=> 'The hive was not found'
            ], Response::HTTP_NOT_FOUND);
        }
        catch (\Exception $e) {
            return new JsonResponse(
            ['error'=> 'Internal Server Error' . ' ' . $e->getMessage() ],
            Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/hive/{id}/harvest', name: 'api_hives_create_harvest', methods: ['POST'])]
    public function createHarvest(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        Request $request,
        LoggerInterface $logger
    ) : JsonResponse {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(
                    [
                        'success' => false,
                        'message' => 'Unauthorized'
                    ],
                    Response::HTTP_UNAUTHORIZED);
            }

            $content = json_decode($request->getContent(), true);
            if (!is_array($content)) {
                return new JsonResponse([
                    'success'=> false,
                    'message'=> 'Invalid JSON format'
                ],Response::HTTP_BAD_REQUEST);
            }
            
            if (!isset($content['date']) || !isset($content['weightG'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Missing required fields: date and weightG'
                ], Response::HTTP_BAD_REQUEST);
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
                ], Response::HTTP_FORBIDDEN);
            }

            $weightG = intval($content['weightG']);
            if ($weightG < 0) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Weight must be positive or zero'
                ], Response::HTTP_BAD_REQUEST);
            }

            try {
                $date = new \DateTime($content['date']);
            } catch (\Exception $e) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid date format'
                ], Response::HTTP_BAD_REQUEST);
            }

            $harvest = new Harvest();
            $harvest->setDate($date);
            $harvest->setWeightG($weightG);
            $harvest->setHive($hive);

            $entityManager->persist($harvest);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Harvest created successfully',
                'harvest' => [
                    'id' => $harvest->getId(),
                    'date' => $harvest->getDate()->format('Y-m-d'),
                    'weightG' => $harvest->getWeightG(),
                    'hiveId' => $hive->getId()
                ]
            ], Response::HTTP_CREATED);

        }
        catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Internal Server Error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    #[Route('/api/hive/{id}/harvests', name: 'api_hives_get_harvests', methods: ['GET'])]
    public function getHarvests(
        int $id,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ) : JsonResponse {
        try {
            /** @var User $user */
            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], Response::HTTP_UNAUTHORIZED);
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
                    'message' => 'Forbidden'
                ], Response::HTTP_FORBIDDEN);
            }

            $harvests = $hive->getHarvest();
            $harvestsData = [];
            $totalWeightG = 0;

            foreach ($harvests as $harvest) {
                $harvestsData[] = [
                    'id' => $harvest->getId(),
                    'date' => $harvest->getDate()->format('Y-m-d'),
                    'weightG' => $harvest->getWeightG()
                ];
                $totalWeightG += $harvest->getWeightG();
            }

            return new JsonResponse([
                'success' => true,
                'harvests' => $harvestsData,
                'totalWeightKg' => round($totalWeightG / 1000, 2)
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Internal Server Error'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
