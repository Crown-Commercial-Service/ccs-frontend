<?php

namespace App\Controller;

use App\Utils\FrameworkCategories;
use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Studio24\Frontend\Cms\RestData;
use Symfony\Component\HttpFoundation\Request;

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
    public function list(int $page = 1, Request $request)
    {
        $this->api->setCacheKey($request->getRequestUri());
        $results = $this->api->list($page);
        $results->getPagination()->setResultsPerPage(20);

        $data = [
            'pagination' => $results->getPagination(),
            'results'    => $results,
            'categories' => FrameworkCategories::getAll(),
            'pillars'       => FrameworkCategories::getAllPillars()
        ];

        return $this->render('frameworks/list.html.twig', $data);
    }


    public function upcomingDeals(Request $request)
    {
        $this->api->setContentType('upcoming_deals');
        $this->api->setCacheKey($request->getRequestUri());

        // @todo At present need to pass fake ID since API method is intended to return one item with an ID, review this
        $results = $this->api->getOne(0);

        $data = [
            'awarded_pipeline'              => $results->getContent()->get('awarded_pipeline'),
            'underway_pipeline'             => $results->getContent()->get('underway_pipeline'),
            'dynamic_purchasing_systems'    => $results->getContent()->get('dynamic_purchasing_systems'),
            'planned_pipeline'              => $results->getContent()->get('planned_pipeline'),
            'future_pipeline'               => $results->getContent()->get('future_pipeline'),
        ];

        return $this->render('frameworks/upcoming-list.html.twig', $data);
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
    public function listByCategory(string $category, int $page = 1, Request $request)
    {
        // Map category slug to category db value
        $categoryName = FrameworkCategories::getDbValueBySlug($category);
        if ($categoryName === null) {
            $this->redirectToRoute('frameworks_list');
        }

        $this->api->setCacheKey($request->getRequestUri());
        $results = $this->api->list($page, ['category' => $categoryName]);
        $results->getPagination()->setResultsPerPage(20);

        $data = [
            'category'      => $categoryName,
            'category_slug' => $category,
            'pagination'    => $results->getPagination(),
            'results'       => $results,
            'categories'    => FrameworkCategories::getAll(),
            'pillars'       => FrameworkCategories::getAllPillars()
        ];
        return $this->render('frameworks/list.html.twig', $data);
    }

    /**
     * List frameworks by category
     *
     * @param string $pillar
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function listByPillar(string $pillar, int $page = 1, Request $request)
    {
        // Map category slug to category db value
        $pillarName = FrameworkCategories::getDbValueBySlug($pillar);
        if ($pillarName === null) {
            $this->redirectToRoute('frameworks_list');
        }

        $this->api->setCacheKey($request->getRequestUri());
        $results = $this->api->list($page, ['pillar' => $pillarName]);
        $results->getPagination()->setResultsPerPage(20);

        $data = [
            'pillar'        => $pillarName,
            'pillar_slug'   => $pillar,
            'pagination'    => $results->getPagination(),
            'results'       => $results,
            'categories'    => FrameworkCategories::getAll(),
            'pillars'       => FrameworkCategories::getAllPillars()
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
    public function show(string $rmNumber, Request $request)
    {
        $this->api->setCacheKey($request->getRequestUri());
        $results = $this->api->getOne($rmNumber);
        $data = [
            'framework' => $results
        ];
        return $this->render('frameworks/show.html.twig', $data);
    }


    /**
     * List unique suppliers on a framework
     *
     * @param string $rmNumber
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function suppliersOnFramework(string $rmNumber, int $page = 1, Request $request)
    {
        // Set custom API endpoint
        // @todo Find better way to set custom endpoint URLs
        $this->api->getContentModel()->getContentType('framework_suppliers')->setApiEndpoint(sprintf('ccs/v1/framework-suppliers/%s', $rmNumber));
        $this->api->setContentType('framework_suppliers');

        $this->api->setCacheKey($request->getRequestUri());
        $results = $this->api->list($page);
        $results->getPagination()->setResultsPerPage(4);

        $data = [
            'pagination'    => $results->getPagination(),
            'results'       => $results,
            'metadata'      => $results->getMetadata(),
        ];
        return $this->render('frameworks/framework-suppliers.html.twig', $data);
    }


    /**
     * Return suppliers on a lot
     *
     * @param string $rmNumber
     * @param string $lotNumber
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentFieldNotSetException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function suppliersOnLot(string $rmNumber, string $lotNumber, int $page = 1, Request $request)
    {
        $this->api->getContentModel()->getContentType('framework_lot_suppliers')->setApiEndpoint(sprintf('ccs/v1/lot-suppliers/%s/lot/%s', $rmNumber, $lotNumber));
        $this->api->setContentType('framework_lot_suppliers');

        $this->api->setCacheKey($request->getRequestUri());
        $results = $this->api->list($page);
        $results->getPagination()->setResultsPerPage(4);

        $data = [
            'pagination'    => $results->getPagination(),
            'results'       => $results,
            'metadata'      => $results->getMetadata()
        ];

        return $this->render('frameworks/lot-suppliers.html.twig', $data);
    }

}
