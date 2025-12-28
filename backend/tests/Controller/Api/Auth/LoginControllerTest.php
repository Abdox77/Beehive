<?php

namespace App\Tests\Controller\Api\Auth;

use App\Tests\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class LoginControllerTest extends BaseWebTestCase
{
    private array $validUser = [
        'email' => 'logintest@example.com',
        'username' => 'loginTestUser',
        'password' => 'PasswordIsStrong@2026'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :email')
            ->setParameter('email', $this->validUser['email'])
            ->execute();
        
        $this->createUser(
            $this->validUser['email'],
            $this->validUser['username'],
            $this->validUser['password']
        );
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $this->validUser['email'],
                'password' => $this->validUser['password'],
            ])
        );

        self::assertResponseIsSuccessful();
        self::assertJson($this->client->getResponse()->getContent());
        
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
    }

    public function testLoginWithInvalidEmail(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => 'nonexistent@example.com',
                'password' => 'SomePassword123',
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $this->validUser['email'],
                'password' => 'WrongPassword123',
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testLoginWithMissingCredentials(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            '{}'
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLoginWithMissingPassword(): void
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $this->validUser['email']])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }
}
