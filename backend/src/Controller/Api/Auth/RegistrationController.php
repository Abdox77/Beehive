<?php

namespace App\Controller\Api\Auth;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class RegistrationController extends AbstractController
{
    public function __construct(private LoggerInterface $logger) {} 

    #[Route('/api/auth/register', name: 'app_registration', methods: ['POST'])]
    public function register(
        Request $request, 
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            foreach ($data as $key => $value) {
                $this->logger->info("the key : {$key} and the value is {$value}");
            }

            if (!isset($data['email']) || !isset($data['password']) || !isset($data['user']))
            {
                return new JsonResponse(['error'=> 'missing registration info'],
                                 Response::HTTP_BAD_REQUEST);
            }

            $email = $data['email'];
            $userName = $data['user'];
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            $existingUser = $em->getRepository(User::class)->findOneBy(['email'=> $email]);
            if ($existingUser)
            {
                return new JsonResponse(['error' => 'User already exists'], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($email);
            $user->setUsername($userName);
            $user->setPassword($password_hash);
            $errors = $validator->validate($user);
            if ($errors->count() > 0) {
                // $messages = [];
                // foreach ($errors as $violation) {
                //     $messages[] = [
                //         'field' => $violation->getPropertyPath(),
                //         'message' => $violation->getMessage(),
                //     ];
                // }
                // return new JsonResponse(['errors' => $messages], Response::HTTP_BAD_REQUEST);
                throw new \Exception('the credentials were not validated');
            }

            $em->persist($user);
            $em->flush();
            
        } catch (\Exception $e)
        {
            return new JsonResponse([
                            'success' => false,
                            'error'=> 'Invalid registration information'], 
                            Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
                    'success' => true,
                    'message' => 'Registration successful. Please login'], 
            Response::HTTP_CREATED);
    }
}
