<?php

namespace App\Tests\Controller;

use App\Tests\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ProfileControllerTest extends BaseWebTestCase
{
    private string $token;
    private array $testUser = [
        'email' => 'profile@example.com',
        'username' => 'profileUser',
        'password' => 'Test1234'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :email')
            ->setParameter('email', $this->testUser['email'])
            ->execute();
        
        $this->createUser(
            $this->testUser['email'],
            $this->testUser['username'],
            $this->testUser['password']
        );
        
        $this->token = $this->getAuthToken(
            $this->testUser['email'],
            $this->testUser['password']
        );
    }

    public function testGetProfileWithAuthentication(): void
    {
        $this->makeAuthenticatedRequest('POST', '/api/profile', $this->token);

        self::assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        self::assertArrayHasKey('user', $response);
        self::assertEquals($this->testUser['email'], $response['user']['email']);
    }

    public function testGetProfileWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/profile');

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
