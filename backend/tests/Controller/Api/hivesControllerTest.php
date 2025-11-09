<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Api/hivesControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/hives');

        self::assertResponseIsSuccessful();
    }
}
