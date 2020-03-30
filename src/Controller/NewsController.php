<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\PaginationException;
use Studio24\Frontend\Exception\WordpressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Studio24\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsController extends AbstractController
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
        $this->api->setContentType('news');
        $this->api->setCache($cache);
        $this->api->setCacheLifetime(900);
    }

    public function list(Request $request, $page = 1)
    {
        $page = (int) filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $list = $this->api->listPages($page);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('News page not found', $e);
        }

        return $this->render('news/list.html.twig', [
            'url' => sprintf('/news/page/%s', $page),
            'pages' => $list
        ]);
    }

    public function show($slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $page = $this->api->getPageByUrl($request->getRequestUri());
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('News page not found', $e);
        }

        return $this->render('news/show.html.twig', [
            'url' => sprintf('/news/%s', $slug),
            'page' => $page
        ]);
    }
}
