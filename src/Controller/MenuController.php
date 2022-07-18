<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;
use Strata\Frontend\Cms\Wordpress;
use Strata\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MenuController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var Wordpress
     */
    protected $api;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->api = new Wordpress(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $psr6Cache = new Psr16Cache($cache);
        $this->api->setContentType('page');
        $this->api->setCache($psr6Cache);
        $this->api->setCacheLifetime(900);
    }


    /**
     * Generic menu controller
     *
     * @param integer $id
     * @param string $templatePath
     * @param string $currentPath
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menu(int $id, string $currentPath, string $templatePath = 'menus/default-menu.html.twig')
    {
        $id = (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);

        $menu = $this->api->getMenu($id);

        if (empty($menu)) {
            return new Response();
        }

        $cmsBaseUrl = getenv('APP_CMS_BASE_URL');
        $appBaseUrl = getenv('APP_BASE_URL');

        if (empty($cmsBaseUrl) || empty($appBaseUrl)) {
            throw new HttpException(500, 'You must set APP_CMS_BASE_URL and APP_BASE_URL environment variables in your .env or .env.local');
        }

        $menu->setBaseUrls(getenv('APP_CMS_BASE_URL'), getenv('APP_BASE_URL'));

        $menu->setActiveItems($currentPath);

        return $this->render($templatePath, [
            'menu' => $menu,
            'currentPath' => $currentPath,
        ]);
    }
}
