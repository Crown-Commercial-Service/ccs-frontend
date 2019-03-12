<?php

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Studio24\Frontend\Cms\RestData;
use Symfony\Component\HttpFoundation\Request;

class SuppliersController extends AbstractController
{

    /**
     * Suppliers Rest API data
     *
     * @var RestData
     */
    protected $api;

    public function __construct(CacheInterface $cache)
    {
        $this->api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('suppliers');
        $this->api->setCache($cache);
    }


    public function list(int $page = 1, Request $request)
    {
        $this->api->setCacheKey($request->getRequestUri());
        $results = $this->api->list($page);
        $results->getPagination()->setResultsPerPage(20);

        $data = [
            'pagination' => $results->getPagination(),
            'results'    => $results,
        ];

        return $this->render('suppliers/list.html.twig', $data);
    }



    public function show(string $id, Request $request)
    {
        $this->api->setCacheKey($request->getRequestUri());
        $results = $this->api->getOne($id);
        $data = [
            'supplier' => $results
        ];
        return $this->render('suppliers/show.html.twig', $data);
    }
}
