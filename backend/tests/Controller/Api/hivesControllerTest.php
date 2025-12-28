<?php

namespace App\Tests\Controller;

use App\Tests\BaseWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class HivesControllerTest extends BaseWebTestCase
{
    private ?string $authToken = null;
    private ?int $createdHiveId = null;

    private array $testUser = [
        'user' => 'hiveTestUser',
        'email' => 'hivetest@example.com',
        'password' => 'PasswordIsStrong@2026'
    ];

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
    }

    public function testCreateHiveWithValidData(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('hive', $data);
        self::assertArrayHasKey('id', $data['hive']);
        self::assertEquals('Test Hive', $data['hive']['name']);
        $this->createdHiveId = $data['hive']['id'];
    }

    public function testCreateHiveWithoutAuth(): void
    {
        $this->client->request(
            'POST',
            '/api/hive',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateHiveWithMissingName(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHiveWithInvalidLatitude(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Invalid Hive',
                'lat' => 100,
                'lng' => -73.5
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHiveWithInvalidLongitude(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Invalid Hive',
                'lat' => 45.5,
                'lng' => 200
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetHivesWithAuth(): void
    {
        $this->makeAuthenticatedRequest(
            'GET',
            '/api/hive',
            $this->authToken
        );

        self::assertResponseIsSuccessful();
        self::assertJson($this->client->getResponse()->getContent());

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertArrayHasKey('hives', $data);
        self::assertIsArray($data['hives']);
    }

    public function testGetHivesWithoutAuth(): void
    {
        $this->client->request(
            'GET',
            '/api/hive',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeleteHiveWithAuth(): void
    {    
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Hive To Delete',
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );

        $createData = json_decode($this->client->getResponse()->getContent(), true);
        $hiveId = $createData['hive']['id'];

        $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/hive/' . $hiveId,
            $this->authToken
        );

        self::assertResponseIsSuccessful();

        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
    }
    public function testDeleteHiveWithoutAuth(): void
    {
        $this->client->request(
            'DELETE',
            '/api/hive/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeleteNonExistentHive(): void
    {
        $this->makeAuthenticatedRequest(
            'DELETE',
            '/api/hive/99999',
            $this->authToken
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateHarvestWithValidData(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Harvest Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );

        $hiveData = json_decode($this->client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];

        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive/' . $hiveId . '/harvest',
            $this->authToken,
            [
                'date' => '2025-12-24',
                'weightG' => 5000
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
        self::assertArrayHasKey('harvest', $data);
        self::assertEquals(5000, $data['harvest']['weightG']);
        self::assertEquals('2025-12-24', $data['harvest']['date']);
    }

    public function testCreateHarvestWithoutAuth(): void
    {
        $this->client->request(
            'POST',
            '/api/hive/1/harvest',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'date' => '2025-12-24',
                'weightG' => 5000
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateHarvestWithMissingFields(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Missing Fields Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );
        
        $hiveData = json_decode($this->client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];
        
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive/' . $hiveId . '/harvest',
            $this->authToken,
            [
                'date' => '2025-12-24'
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHarvestWithInvalidDate(): void
    {
        // Create a hive first
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Invalid Date Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );
        
        $hiveData = json_decode($this->client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];
        
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive/' . $hiveId . '/harvest',
            $this->authToken,
            [
                'date' => 'invalid-date',
                'weightG' => 5000
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHarvestWithNegativeWeight(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Negative Weight Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );

        $hiveData = json_decode($this->client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];

        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive/' . $hiveId . '/harvest',
            $this->authToken,
            [
                'date' => '2025-12-24',
                'weightG' => -100
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHarvestForNonExistentHive(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive/99999/harvest',
            $this->authToken,
            [
                'date' => '2025-12-24',
                'weightG' => 5000
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetHarvestsWithValidHive(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Get Harvests Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );

        $hiveData = json_decode($this->client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];

        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive/' . $hiveId . '/harvest',
            $this->authToken,
            [
                'date' => '2025-12-24',
                'weightG' => 3000
            ]
        );

        $this->makeAuthenticatedRequest(
            'GET',
            '/api/hive/' . $hiveId . '/harvests',
            $this->authToken
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
        self::assertArrayHasKey('harvests', $data);
        self::assertArrayHasKey('totalWeightKg', $data);
        self::assertIsArray($data['harvests']);
        self::assertGreaterThan(0, count($data['harvests']));
    }

    public function testGetHarvestsWithoutAuth(): void
    {
        $this->client->request(
            'GET',
            '/api/hive/1/harvests',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json']
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetHarvestsForNonExistentHive(): void
    {
        $this->makeAuthenticatedRequest(
            'GET',
            '/api/hive/99999/harvests',
            $this->authToken
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetHarvestsTotalWeightCalculation(): void
    {
        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive',
            $this->authToken,
            [
                'name' => 'Total Weight Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ]
        );

        $hiveData = json_decode($this->client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];

        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive/' . $hiveId . '/harvest',
            $this->authToken,
            [
                'date' => '2025-12-20',
                'weightG' => 2000
            ]
        );

        $this->makeAuthenticatedRequest(
            'POST',
            '/api/hive/' . $hiveId . '/harvest',
            $this->authToken,
            [
                'date' => '2025-12-21',
                'weightG' => 3000
            ]
        );

        $this->makeAuthenticatedRequest(
            'GET',
            '/api/hive/' . $hiveId . '/harvests',
            $this->authToken
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertEquals(5.0, $data['totalWeightKg']); 
    }
}
