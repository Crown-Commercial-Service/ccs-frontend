<?php

declare(strict_types=1);

namespace App\Tests\App\Utils;
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PageTest extends WebTestCase
{

    /**
     * @dataProvider provideUrls
     */
    public function testPageIsSuccessful($url)
    {

        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertTrue($client->getResponse()->isSuccessful());
    }

    public function provideUrls()
    {
        return [
            ['/'],
            ['/agreements'],
            ['/suppliers'],
            ['/agreements/upcoming'],
            ['/products-and-services'],
            ['/sectors'],

            ['/covid-19'],
            ['/about-ccs'],
            ['/contact'],
            ['/events'],
            ['/news'],
            ['/buy-and-supply'],
        ];
    }
}
