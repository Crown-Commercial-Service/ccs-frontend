<?php

declare(strict_types=1);

namespace App\Tests\App\Controller\Double;

use Symfony\Component\HttpFoundation\Response;

class MockMenuController
{
    /**
     * A stable test double method that mimics the expected menu action signature
     */
    public function menu(): Response
    {
        return new Response('<nav class="global-navigation">Mocked Navigation Menu</nav>');
    }
}