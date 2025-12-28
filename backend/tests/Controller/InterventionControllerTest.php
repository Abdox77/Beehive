<?php

namespace App\Tests\Controller;

use App\Tests\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class InterventionControllerTest extends BaseWebTestCase
{
    private array $testUser = [
        'user' => 'interventionTestUser',
        'email' => 'interventiontest@example.com',
        'password' => 'PasswordIsStrong@2026'
    ];

    private ?string $authToken = null;
    private ?int $hiveId = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\Harvest h WHERE h.hive IN (SELECT hv.id FROM App\Entity\Hive hv WHERE hv.owner IN (SELECT u.id FROM App\Entity\User u WHERE u.email = :email))')
            ->setParameter('email', $this->testUser['email'])
            ->execute();
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\Intervention i WHERE i.hive IN (SELECT hv.id FROM App\Entity\Hive hv WHERE hv.owner IN (SELECT u.id FROM App\Entity\User u WHERE u.email = :email))')
            ->setParameter('email', $this->testUser['email'])
            ->execute();
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\Hive h WHERE h.owner IN (SELECT u.id FROM App\Entity\User u WHERE u.email = :email)')
            ->setParameter('email', $this->testUser['email'])
            ->execute();
        
        $this->entityManager->createQuery('DELETE FROM App\Entity\User u WHERE u.email = :email')
            ->setParameter('email', $this->testUser['email'])
            ->execute();
        
        $this->createUser(
            $this->testUser['email'],
            $this->testUser['user'],
            $this->testUser['password']
        );
        
        $this->authToken = $this->getAuthToken(
            $this->testUser['email'],
            $this->testUser['password']
        );
        
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Hive for Intervention',
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );
        
        $hiveData = json_decode($this->client->getResponse()->getContent(), true);
        $this->hiveId = $hiveData['hive']['id'] ?? null;
    }

    public function testAddInterventionWithValidData(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/intervention/' . $this->hiveId,
            $this->authToken,
            ['note' => 'Hive checked for mites']
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
        self::assertEquals('Hive checked for mites', $data['intervention']['note']);
    }

    public function testAddInterventionMissingNote(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/intervention/' . $this->hiveId,
            $this->authToken,
            []
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testAddInterventionUnauthorized(): void
    {
        $this->client->request(
            'POST',
            '/api/intervention/' . $this->hiveId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'note' => 'Unauthorized test'
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertJson($this->client->getResponse()->getContent());
    }
}
