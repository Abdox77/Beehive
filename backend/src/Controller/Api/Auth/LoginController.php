<?php

namespace App\Controller\Api\Auth;

use App\Middleware\JwtManager;
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
    public function __construct(private LoggerInterface $logger, private JwtManager $jwtManager) { }

    #[Route(path:'/login', name:'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->logger->info('The email is'. $data['email'] .'and the password is'. $data['password']);
        if (null === $data || ($data && !is_array($data))) {
            return new JsonResponse(['error' => 'Error invalid request'], Response::HTTP_BAD_REQUEST);
        }
        
        $email = $data['email'];
        $password = $data['password'];

        // i should do the lookup here, after i create the user entity
        // hashThe password and compare it to the hashedpassword in the db
        // and get the user id
      
        $userId = 42;
        $tokenData = ['jwt_usr_id' => $userId, 'jwt_email' => $email];
        $token = $this->jwtManager->createToken($tokenData);
        
        $this->logger->info('The token is'. $token);
        return new JsonResponse(
            [ 'success' => true , 'token' => $token ],
            Response::HTTP_OK
        );
    }


    /**
     * to refresh the token, to implement later on
     */
    #[Route(path:'/refresh', name:'', methods: [])]
    public function refresh(Request $request) {
        
    }
}

