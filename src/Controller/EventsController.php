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

class EventsController extends AbstractController
{
    /**
     * Events Rest API data
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
        $this->api->setContentType('events');
        $this->api->setCache($cache);
        $this->api->setCacheLifetime(120);
    }

    public function list(Request $request, $page = 1)
    {
        $page = (int) filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        $this->api->setCacheKey($request->getRequestUri());

        /**
         * Get taxonomies for filtering results
         */
        $sectors = $this->api->getAllTerms('sectors');
        $productsServices = $this->api->getAllTerms('products_services');

        $productServiceFilter = $request->query->get('product_service');
        $sectorFilter         = $request->query->get('sector');

        /**
         * Define options for Rest API query
         */
        $options = [
            'products_services' => $productServiceFilter,
            'sectors' => $sectorFilter,
        ];

        try {
            $list = $this->api->listPages($page, $options);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Events page not found', $e);
        }

        return $this->render('events/list.html.twig', [
            'url' => sprintf('/events/page/%s', $page),
            'events' => $list,
            'pagination' => $list->getPagination(),
            'sectors' => $sectors,
            'products_services' => $productsServices,
            'filters' => $options
        ]);
    }

    public function show($slug, Request $request)
    {
        $sanitisedSlug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $event = $this->api->getPageByUrl('/news/events/' . $sanitisedSlug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Event not found', $e);
        }

        $data = [
            'event'       => $event
        ];

        return $this->render('events/show.html.twig', $data);
    }
}
