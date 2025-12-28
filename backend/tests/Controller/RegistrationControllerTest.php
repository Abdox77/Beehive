<?php

namespace App\Tests\Controller;

use App\Tests\BaseWebTestCase;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Component\HttpFoundation\Response;

final class RegistrationControllerTest extends BaseWebTestCase
{
    public function testRegistrationWithValidData(): array 
    {
        $userData = [
            'user' => 'userName'.uniqid(),
            'email' => 'testuser'. uniqid() .'@example.com',
            'password' => 'PasswordIsStrong@2026'
        ];

        $this->client->request('POST',
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
        $this->client->request(
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
        $response = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $response);
        self::assertNotEmpty($response['token']);
    }

    public function testRegistrationWithMissingData(): void
    {
        $this->client->request('POST',
                '/api/auth/register', 
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'], 
                '{}');
        
        self::assertResponseStatusCodeSame(422);
        self::assertJson($this->client->getResponse()->getContent());
    }
}
