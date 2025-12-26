<?php

namespace App\Controller\Api;

use App\Security\Authenticated;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Authenticated]
final class ProfileController extends AbstractController
{
    #[Route(path: '/api/profile', name: 'app_profile', methods: ['POST'])]
    public function userProfile(Request $request): JsonResponse
    {
        return new JsonResponse([
            'succes' => 'you\'re now authenticated',
            'token' => $request->headers->get('jwt_claims')],
                            Response::HTTP_OK);
    }
}
