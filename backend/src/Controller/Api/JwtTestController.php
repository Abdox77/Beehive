<?php

namespace App\Controller\Api;


use App\Middleware\JwtManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JwtTestController extends AbstractController
{
    #[Route('/api/_test/jwt', methods: ['GET'])]
    public function __invoke(JwtManager $jwt, LoggerInterface $logger): JsonResponse
    {
        $token = $jwt->createToken(['something' => 42, 'email' => 'BornToCode42@42.fr']);
        $valid = $jwt->validateToken($token);
        $claim = $jwt->decodeToken($token);

        $logger->info('this api test jwt was invoked');

        return new JsonResponse([
            'token' => $token,
            'valid' => $valid,
            'claim' => $claim,
        ], Response::HTTP_OK);
    }
}
