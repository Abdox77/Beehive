<?php

namespace App\Controller\Api\Auth;

use App\Entity\User;
use App\Middleware\JwtManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Security\Authenticated;


#[Route('/api/auth', name: 'app_api_auth')]
final class LoginController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger, 
        private JwtManager $jwtManager,
        private EntityManagerInterface $em) 
    { }

    #[Route(path:'/login', name:'_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if ($data === null || !is_array($data) || !isset($data['email']) || !isset($data['password'])) 
            {
                return new JsonResponse(['error' => 'Error invalid request'], Response::HTTP_BAD_REQUEST);
            }
            // $this->logger->info('The email is'. $data['email'] .'and the password is'. $data['password']);
            
            $email = $data['email'];
            $password = $data['password'];
            $user = $this->em->getRepository(User::class)->findOneBy(['email'=> $email]);
            if ($user === null)
            {
                return new JsonResponse(['error'=> 'user was not found'], 
                                    Response::HTTP_UNAUTHORIZED);
            }
            
            if(!password_verify($password, $user->getPassword()))
            {
                return new JsonResponse(['error'=> 'invalid password'], 
                                        Response::HTTP_UNAUTHORIZED);
            }
            $payload = [
                    'jwt_usr_id' => $user->getId(), 
                    'jwt_email' => $user->getEmail()
                ];
            $token = $this->jwtManager->createToken($payload);
            
            return new JsonResponse(
                [ 'success' => true , 
                        'message' => 'you\'re loged in',
                        'token' => $token],
                Response::HTTP_OK
            );
        }
        catch (\Exception $e) {
            return new JsonResponse(
            [
                'success' => false,
                'message'=> 'Internal Server Error' 
            ],
            Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * to refresh the token, to implement later on
     */
    // #[Route(path:'/refresh', name:'')]
    // public function refresh(Request $request) {
        
    // }
}

