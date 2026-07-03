<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;
use Strata\Frontend\Cms\Wordpress;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MenuController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var Wordpress
     */
    protected Wordpress $api;
    protected string $appCmsBaseUrl;
    protected string $appBaseUrl;

    public function __construct(
        CacheItemPoolInterface $cache,
        Wordpress $api,
        string $appCmsBaseUrl,
        string $appBaseUrl
    ) {
        $this->api = $api;
        $this->appCmsBaseUrl = $appCmsBaseUrl;
        $this->appBaseUrl = $appBaseUrl;

        $psr16Cache = new Psr16Cache($cache);
        $this->api->setContentType('page');
        $this->api->setCache($psr16Cache);
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
    public function menu(int $id, string $currentPath, string $templatePath = 'menus/default-menu.html.twig', bool $inlineMenu = false)
    {
        $id = (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);

        $menu = $this->api->getMenu($id);

        if (empty($menu)) {
            return new Response();
        }

        $menu->setBaseUrls($this->appCmsBaseUrl, $this->appBaseUrl);

        $menu->setActiveItems($currentPath);

        return $this->render($templatePath, [
            'menu' => $menu,
            'currentPath' => $currentPath,
            'inlineMenu' => $inlineMenu,
        ]);
    }
}
