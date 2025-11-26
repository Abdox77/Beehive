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

    public function testUpdateHiveWithValidData(): void
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
                'name' => 'Hive To Update',
                'lat' => 45.5,
                'lng' => -73.5
            ])
        );

        $createData = json_decode($client->getResponse()->getContent(), true);
        $hiveId = $createData['hive']['id'];

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request(
            'PUT',
            '/api/hive/' . $hiveId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Updated Hive Name',
                'lat' => 46.0,
                'lng' => -74.0
            ])
        );

        self::assertResponseIsSuccessful();

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertEquals('Updated Hive Name', $data['hive']['name']);
        self::assertEquals(46.0, $data['hive']['lat']);
        self::assertEquals(-74.0, $data['hive']['lng']);
    }

    public function testUpdateHiveWithoutAuth(): void
    {
        $client = static::createClient();
        $client->request(
            'PUT',
            '/api/hive/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated Name',
                'lat' => 46.0,
                'lng' => -74.0
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateNonExistentHive(): void
    {
        $client = static::createClient();
        $client->request(
            'PUT',
            '/api/hive/99999',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Updated Name',
                'lat' => 46.0,
                'lng' => -74.0
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateHiveWithMissingFields(): void
    {
        $client = static::createClient();
        $client->request(
            'PUT',
            '/api/hive/1',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'name' => 'Only Name'
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
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
}
