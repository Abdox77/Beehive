<?php

namespace App\Tests\Controller\Api\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LoginControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/auth/login');

        self::assertResponseIsSuccessful();
    }
}
