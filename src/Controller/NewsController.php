<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\WordpressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

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
        $this->api->setCacheLifetime(1800);
    }

    public function list($page = 1, Request $request)
    {
        $page = (int) filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        $this->api->setCacheKey($request->getRequestUri());
        $list = $this->api->listPages($page);

        return $this->render('news/list.html.twig', [
            'url' => sprintf('/news/page/%s', $page),
            'pages' => $list
        ]);
    }

    public function show($slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());
        $page = $this->api->getPageBySlug($slug);

        return $this->render('news/show.html.twig', [
            'url' => sprintf('/news/%s', $slug),
            'page' => $page
        ]);
    }
}
