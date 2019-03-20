<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\ContentModel\ContentModel;
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

    public function __construct(CacheInterface $cache)
    {
        $this->api = new Wordpress(
            getenv('APP_API_BASE_URL')
        );
        $this->api->setCache($cache);
    }


    /**
     * Generic menu controller
     *
     * @param integer $id
     * @param string $templatePath
     * @param string $currentPath
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function menu(int $id, string $templatePath = 'menus/default-menu.html.twig', string $currentPath)
    {
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
