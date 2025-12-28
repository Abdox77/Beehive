<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\Depends;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RegistrationControllerTest extends WebTestCase
{
    public function testRegistrationWithValidData(): array 
    {
        $client = static::createClient();
        $userData = [
            'user' => 'userName'.uniqid(),
            'email' => 'testuser'. uniqid() .'@example.com',
            'password' => 'PasswordIsStrong@2026'
        ];

        $client->request('POST',
                '/api/auth/register', 
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($userData));
        
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        return $userData;
    }

    #[Depends('testRegistrationWithValidData')]
    public function testLoginWithNewlyRegisteredUser(array $userData): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $userData['email'],
                'password' => $userData['password'],
            ])
        );

        self::assertResponseIsSuccessful();
        $response = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $response);
        self::assertNotEmpty($response['token']);
    }

    public function testRegistrationWithMissingData(): void
    {
        $client = static::createClient();
        $client->request('POST',
                '/api/auth/register', 
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'], 
                '{}');
        
        self::assertResponseStatusCodeSame(422);
        self::assertJson($client->getResponse()->getContent());
    }
}
