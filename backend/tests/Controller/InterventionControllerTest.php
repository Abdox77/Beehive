<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class InterventionControllerTest extends WebTestCase
{
    private static array $testUser = [
        'user' => 'interventionTestUser',
        'email' => 'interventiontest@example.com',
        'password' => 'PasswordIsStrong@2026'
    ];

    private static ?string $authToken = null;
    private static ?int $hiveId = null;

    protected function setUp(): void
    {
        if (!self::$authToken) {
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
                    'name' => 'Hive for Intervention',
                    'lat' => 45.5,
                    'lng' => -73.5
                ])
            );

            $hiveData = json_decode($client->getResponse()->getContent(), true);
            self::$hiveId = $hiveData['hive']['id'] ?? null;
            self::ensureKernelShutdown();
        }
    }

    public function testAddInterventionWithValidData(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/intervention/' . self::$hiveId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([
                'note' => 'Hive checked for mites'
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);
        self::assertJson($client->getResponse()->getContent());

        $data = json_decode($client->getResponse()->getContent(), true);
        self::assertTrue($data['success']);
        self::assertEquals('Hive checked for mites', $data['intervention']['note']);
    }

    public function testAddInterventionMissingNote(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/intervention/' . self::$hiveId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . self::$authToken
            ],
            json_encode([])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertJson($client->getResponse()->getContent());
    }

    public function testAddInterventionUnauthorized(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/intervention/' . self::$hiveId,
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'note' => 'Unauthorized test'
            ])
        );

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
        self::assertJson($client->getResponse()->getContent());
    }
}
