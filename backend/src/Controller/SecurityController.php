<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'app_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ?JWTTokenManagerInterface $JWTManager = null
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['email']) || !isset($data['password'])){
                return new JsonResponse(
                    ['error' => 'Email and Password are required'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $user = $entityManager->getRepository(User::class)->findByEmail($data['email']);
            if (null === $user) {
                return new JsonResponse(
                    ['error' => 'Invalid credentials'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            if(!$passwordHasher->isPasswordValid($user, $data['password'])){
                return new JsonResponse(
                    ['error'=> 'Invalid credentials'],
                    Response::HTTP_UNAUTHORIZED
                );
            }

            if($JWTManager){
                $token = $JWTManager->create($user);
                return new JsonResponse([
                    'token' => $token,
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail()
                        ]
                    ]);
            }

            return new JsonResponse([
                'message' => 'Login successful',
                'user' => [
                    'id'=> $user->getId(),
                    'email'=> $user->getEmail()
                ]
            ]);
        }
        catch (\Throwable $e) {
            return new JsonResponse([
                'error'=> 'Internal Server Error',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    // #[Route('/api/logout', name: 'app_logout', methods: ['POST'])]
    // public function logout(
    //     Request $request, 
    //     JWTTokenManagerInterface $JWTManager
    // ): JsonResponse
    // {
    //     try{
    //         return new JsonResponse([
    //             'message'=> 'Logout successful'
    //         ]);
    //     }
    //     catch (\Throwable $e) {
    //         return new JsonResponse([
    //             'error'=> 'Internal Server Error',
    //             ], Response::HTTP_INTERNAL_SERVER_ERROR
    //         );  
    //     }
    // }
}
