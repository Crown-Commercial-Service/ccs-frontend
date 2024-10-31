<?php

declare(strict_types=1);

namespace App\Tests\App\Utils;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AgreementTest extends WebTestCase
{
    public function testredirectionForMCF3()
    {
        $client = static::createClient();
        $client->request('GET', '/agreements/RM6187_cas');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertResponseRedirects('/agreements/RM6187');
    }
}
