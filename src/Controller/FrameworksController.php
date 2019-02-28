<?php

namespace App\Controller;

use App\Utils\FrameworkCategories;
use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Studio24\Frontend\Cms\RestData;

class FrameworksController extends AbstractController
{

    /**
     * Frameworks Rest API data
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
        $this->api->setContentType('frameworks');
        $this->api->setCache($cache);
    }

    /**
     * List frameworks
     *
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function list(int $page = 1)
    {
        $results = $this->api->list($page);
        $data = [
            'pagination' => $results->getPagination(),
            'results'    => $results,
            'categories' => FrameworkCategories::getAll()
        ];
        dump($results);

        return $this->render('frameworks/list.html.twig', $data);
    }

    /**
     * List frameworks by category
     *
     * @param string $category
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function listByCategory(string $category, int $page = 1)
    {
        // Map category slug to category db value
        $categoryName = FrameworkCategories::getDbValueBySlug($category);
        if ($categoryName === null) {
            $this->redirectToRoute('frameworks_list');
        }

        $results = $this->api->list($page, ['category' => $categoryName]);
        
        $data = [
            'category'      => $categoryName,
            'category_slug' => $category,
            'pagination'    => $results->getPagination(),
            'results'       => $results,
            'categories'    => FrameworkCategories::getAll()
        ];
        return $this->render('frameworks/list.html.twig', $data);
    }

    /**
     * Show one framework
     *
     * @param string $rmNumber
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentFieldNotSetException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function show(string $rmNumber)
    {
        $results = $this->api->getOne($rmNumber);
        $data = [
            'framework' => $results
        ];
        return $this->render('frameworks/show.html.twig', $data);
    }

    public function suppliersOnFramework(string $rmNumber)
    {
        $results = $this->api->getOne($rmNumber);
        $data = [
            'framework' => $results
        ];
        return $this->render('frameworks/framework-suppliers.html.twig', $data);
    }

    public function suppliersOnLot(string $rmNumber, string $lotNumber)
    {
        // @todo Create Lot API endpoint, grabbing data from framework for now
        $results = $this->api->getOne($rmNumber);

        $lots = $results->getContent()->get('lots');
        $lot = false;
        if (is_iterable($lots)) {
            foreach ($lots as $item) {
                if ($item['lot_number'] == $lotNumber) {
                    $lot = $item;
                }
            }
        }

        $data = [
            'framework' => $results,
            'lot' => $lot
        ];

        return $this->render('frameworks/lot-suppliers.html.twig', $data);
    }

}