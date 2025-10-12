<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


final class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        LoggerInterface $logger
    ) {
        try {
            $data = json_decode($request->getContent(), true);

            $logger->info('Registration data: ===> ', ['data' => $data]);
            
            if(!isset($data['email']) || !isset($data['password'])) {
                return new JsonResponse(
                    ['error' => 'Email and password are required'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            if(!isset($data['userName'])) {
                return new JsonResponse(
                    ['error' => 'userName is required'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $existingUser = $entityManager->getRepository(User::class)->findByEmail($data['email']);
            if ($existingUser) {
                return new JsonResponse(
                    ['error' => 'User already exists'],
                    Response::HTTP_CONFLICT
                );
            }

            $user = new User();
            $user->setEmail($data['email']);
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $errors = $validator->validate($user);
            if(count($errors) > 0) {
                $errorMessages = [];
                foreach($errors as $error) {
                    $errorMessages[$error->getPropertyPath()] = $error->getMessage();
                }
                return new JsonResponse(
                    ['errors' => $errorMessages],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $entityManager->persist($user);
            $entityManager->flush();
            return new JsonResponse(
                ['message' => 'User registered successfully'],
                Response::HTTP_CREATED
            );
        }
        catch(\Throwable $e) {
            return new JsonResponse(
                ['error' => "Internal Server Error"],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }    
}
