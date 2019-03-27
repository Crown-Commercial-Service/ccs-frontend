<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\FailedRequestException;
use Studio24\Frontend\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $this->api->setCacheLifetime(1800);
    }

    /**
     * Homepage
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function home(Request $request)
    {
        $this->api->setCacheKey($request->getRequestUri());

        $this->api->setContentType('news');
        $news = $this->api->listPages(1, ['limit' => 3]);

        return $this->render('pages/home.html.twig', [
            'news' => $news
        ]);
    }

    /**
     * Generic page controller
     *
     * @param string $slug
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ApiException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function page(string $slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_STRING);

        // @todo May need to look at mapping URLs to page IDs in the future
        try {
            $this->api->setCacheKey($request->getRequestUri());
            $page = $this->api->getPageByUrl($request->getRequestUri());

        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        // Create breadcrumb
        // @todo Improve breadcrumb creation
        $parts = explode('/', trim($slug, '/'));
        array_pop($parts);
        $breadcrumb = [];
        $link = '';
        foreach ($parts as $part) {
            $name = ucfirst(str_replace('-', ' ', $part));
            $link .= '/' . $part;
            $breadcrumb[$link] = $name;
        }

        return $this->render('pages/page.html.twig', [
            'page'               => $page,
            'breadcrumb_parents' => $breadcrumb,
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
