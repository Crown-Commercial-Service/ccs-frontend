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
        $this->api->setCacheLifetime(1800);
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

    /**
     * Search suppliers
     *
     * @see http://ccs-agreements.cabinetoffice.localhost/wp-json/ccs/v1/suppliers/?keyword=503645152
     * @see http://ccs-agreements.cabinetoffice.localhost/wp-json/ccs/v1/suppliers/?keyword=RM6073
     * @see http://ccs-agreements.cabinetoffice.localhost/wp-json/ccs/v1/suppliers/?keyword=courier
     *
     * @param Request $request
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function search(Request $request, int $page = 1)
    {
        // Get search query
        $query = filter_var($request->query->get('q'), FILTER_SANITIZE_STRING);

        // Type param
        $type = filter_var($request->query->get('t'), FILTER_SANITIZE_STRING);
        if (!empty($type) && $type == 'old') {
            /**
             * Replace dash separated with spaces (+)
             * From: q=bramble-hub-limited
             * To:   q=bramble+hub+limited
             */
            $query = str_replace('-', '+', $query);
        }

        $this->api->setCacheKey($request->getRequestUri());
        $results = $this->api->list($page, [
            'keyword'   => $query,
            'limit'     => 20,
        ]);

        $data = [
            'query'         => $query,
            'pagination'    => $results->getPagination(),
            'results'       => $results,
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
