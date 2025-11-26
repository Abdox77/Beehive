<?php

namespace App\Tests\Controller\Api\Auth;

use PHPUnit\Framework\Attributes\Depends;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;


final class LoginControllerTest extends WebTestCase
{
    private array $validUser = [
        'email' => 'logintest@example.com',
        'password' => 'PasswordIsStrong@2026'
    ];

    private static bool $userCreated = false;

    protected function setUp(): void
    {
        if (!self::$userCreated) {
            $client = static::createClient();
            $client->request(
                'POST',
                '/api/auth/register',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'user' => 'loginTestUser' . uniqid(),
                    'email' => $this->validUser['email'],
                    'password' => $this->validUser['password']
                ])
            );
            self::$userCreated = true;
            self::ensureKernelShutdown();
        }
    }

    public function testLoginWithValidCredentials(): void
    {
        $client = static::createClient();
        $client->request(
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
        self::assertJson($client->getResponse()->getContent());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $data);
    }

    public function testLoginWithInvalidEmail(): void
    {
        $client = static::createClient();
        $client->request(
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
        $client = static::createClient();
        $client->request(
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
        $client = static::createClient();
        $client->request(
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
        $client = static::createClient();
        $client->request(
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
