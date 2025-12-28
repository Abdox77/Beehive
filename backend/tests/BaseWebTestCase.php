<?php

namespace App\Tests;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class BaseWebTestCase extends WebTestCase
{
    protected $client;
    protected $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    protected function createUser(string $email, string $username, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    protected function getAuthToken(string $email, string $password): ?string
    {
        $this->client->request('POST', '/api/auth/login', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => $email,
            'password' => $password,
        ]));

        if ($this->client->getResponse()->getStatusCode() !== 200) {
            $content = $this->client->getResponse()->getContent();
            throw new \RuntimeException(
                sprintf(
                    'Login failed with status %d: %s',
                    $this->client->getResponse()->getStatusCode(),
                    $content
                )
            );
        }

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $token = $response['token'] ?? null;
        
        if ($token === null) {
            throw new \RuntimeException('Login response did not contain a token');
        }
        
        return $token;
    }

    protected function makeAuthenticatedRequest(string $method, string $uri, string $token, array $data = []): void
    {
        $this->client->request($method, $uri, [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ], empty($data) ? null : json_encode($data));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if ($this->entityManager) {
            $this->entityManager->close();
        }
    }
}
