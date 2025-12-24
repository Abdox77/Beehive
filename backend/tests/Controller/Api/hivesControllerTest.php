<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class hivesControllerTest extends WebTestCase
{
    private static ?string $authToken = null;
    private static ?int $createdHiveId = null;

    private static array $testUser = [
        'user' => 'hiveTestUser',
        'email' => 'hivetest@example.com',
        'password' => 'PasswordIsStrong@2026'
    ];

    protected function setUp(): void
    {
        if (self::$authToken === null) {
            $client = static::createClient();
            $client->request(
                'POST',
                '/api/auth/register',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'user' => self::$testUser['user'] . uniqid(),
                    'email' => self::$testUser['email'],
                    'password' => self::$testUser['password']
                ])
            );

            self::ensureKernelShutdown();
            $client = static::createClient();
            $client->request(
                'POST',
                '/api/auth/login',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'email' => self::$testUser['email'],
                    'password' => self::$testUser['password']
                ])
            );

            $data = json_decode($client->getResponse()->getContent(), true);
            self::$authToken = $data['token'] ?? null;

            self::ensureKernelShutdown();
        }
    }

    public function testCreateHiveWithValidData(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('hive', $data);
        self::assertArrayHasKey('id', $data['hive']);
        self::assertEquals('Test Hive', $data['hive']['name']);
        self::$createdHiveId = $data['hive']['id'];
    }

    public function testCreateHiveWithoutAuth(): void
    {
        $client = static::createClient();
        $client->request(
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
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'lat' => 45.5,
                'lng' => -73.5
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHiveWithInvalidLatitude(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Invalid Hive',
                'lat' => 100,
                'lng' => -73.5
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHiveWithInvalidLongitude(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Invalid Hive',
                'lat' => 45.5,
                'lng' => 200
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetHivesWithAuth(): void
    {
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ]
        );

        self::assertResponseIsSuccessful();
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('hives', $data);
        self::assertIsArray($data['hives']);
    }

    public function testGetHivesWithoutAuth(): void
    {
        $client = static::createClient();
        $client->request(
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
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Hive To Delete',
                'lat' => 45.5,
                'lng' => -73.5
            ])
        );

        $createData = json_decode($client->getResponse()->getContent(), true);
        $hiveId = $createData['hive']['id'];

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'DELETE',
            '/api/hive/' . $hiveId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ]
        );

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
    }
    public function testDeleteHiveWithoutAuth(): void
    {
        $client = static::createClient();
        $client->request(
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
        $client = static::createClient();
        $client->request(
            'DELETE',
            '/api/hive/99999',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateHarvestWithValidData(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Harvest Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ])
        );

        $hiveData = json_decode($client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive/' . $hiveId . '/harvest',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'date' => '2025-12-24',
                'weightG' => 5000
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
        self::assertArrayHasKey('harvest', $data);
        self::assertEquals(5000, $data['harvest']['weightG']);
        self::assertEquals('2025-12-24', $data['harvest']['date']);
    }

    public function testCreateHarvestWithoutAuth(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive/1',
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
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'date' => '2025-12-24'
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHarvestWithInvalidDate(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'date' => 'invalid-date',
                'weightG' => 5000
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHarvestWithNegativeWeight(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Negative Weight Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ])
        );

        $hiveData = json_decode($client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive/' . $hiveId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'date' => '2025-12-24',
                'weightG' => -100
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testCreateHarvestForNonExistentHive(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive/99999',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'date' => '2025-12-24',
                'weightG' => 5000
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetHarvestsWithValidHive(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Get Harvests Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ])
        );

        $hiveData = json_decode($client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive/' . $hiveId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'date' => '2025-12-24',
                'weightG' => 3000
            ])
        );

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/hive/' . $hiveId . '/harvests',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ]
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
        self::assertArrayHasKey('harvests', $data);
        self::assertArrayHasKey('totalWeightKg', $data);
        self::assertIsArray($data['harvests']);
        self::assertGreaterThan(0, count($data['harvests']));
    }

    public function testGetHarvestsWithoutAuth(): void
    {
        $client = static::createClient();
        $client->request(
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
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/hive/99999/harvests',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetHarvestsTotalWeightCalculation(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Total Weight Test Hive',
                'lat' => 45.5,
                'lng' => -73.5
            ])
        );

        $hiveData = json_decode($client->getResponse()->getContent(), true);
        $hiveId = $hiveData['hive']['id'];

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive/' . $hiveId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'date' => '2025-12-20',
                'weightG' => 2000
            ])
        );

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/hive/' . $hiveId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'date' => '2025-12-21',
                'weightG' => 3000
            ])
        );

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'GET',
            '/api/hive/' . $hiveId . '/harvests',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ]
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals(5.0, $data['totalWeightKg']); 
    }
}
