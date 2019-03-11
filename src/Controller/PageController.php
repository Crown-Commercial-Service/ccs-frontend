<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class PageController extends AbstractController
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
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('page');
        $this->api->setCache($cache);
    }

    /**
     * Generic page controller
     *
     * @param string $slug
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function page(string $slug, Request $request)
    {
        $this->api->setCacheKey($request->getRequestUri());
        $page = $this->api->getPageBySlug($slug);

        return $this->render('pages/page.html.twig', [
            'page' => $page
        ]);
    }

    /**
     * Simple healthcheck
     *
     * @return JsonResponse
     */
    public function healthcheck()
    {
        $required = '7.1.3';
        if (version_compare(PHP_VERSION, $required) < 0) {
            return new JsonResponse(['message' => sprintf("PHP version must be %s or above, found '%s'", $required, PHP_VERSION)], 500);
        }

        // Check Composer has loaded required classes
        $required = [
            'Symfony\Bundle\FrameworkBundle\Controller\AbstractController',
            'Studio24\Frontend\Cms\RestData',
            'Studio24\Frontend\Cms\Wordpress'
        ];
        foreach ($required as $class) {
            if (!class_exists($class)) {
                return new JsonResponse(['message' => sprintf("Class '%s' does not exist", $class)], 500);
            }
        }

        // Check required environment variables
        $required = [
            'APP_API_BASE_URL',
            'APP_ENV'
        ];
        foreach ($required as $variable) {
            if (empty(getenv($variable))) {
                return new JsonResponse(['message' => sprintf("Environment variable '%s' not set", $variable)], 500);
            }
        }

        return new JsonResponse(['message' => 'OK']);
    }

}
