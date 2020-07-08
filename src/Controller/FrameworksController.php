<?php

namespace App\Controller;

use App\Utils\FrameworkCategories;
use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\PaginationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Studio24\Frontend\Cms\RestData;
use Symfony\Component\HttpFoundation\Request;
use Studio24\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FrameworksController extends AbstractController
{

    /**
     * Frameworks Rest API data
     *
     * @var RestData
     */
    protected $api;

    /**
     * @var \Studio24\Frontend\Cms\RestData
     */
    protected $searchApi;

    public function __construct(CacheInterface $cache)
    {
        $this->api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('frameworks');
        $this->api->setCache($cache);
        $this->api->setCacheLifetime(900);

        $this->searchApi = new RestData(
            getenv('SEARCH_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );

        $this->searchApi->setContentType('frameworks');
        $this->searchApi->setCache($cache);
        $this->searchApi->setCacheLifetime(1);
    }

    /**
     * List frameworks
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
    public function list(Request $request, int $page = 1)
    {
        /**
         * Detect incoming old links from ccs-agreements domain
         * E.g. f[0]=im_field_category:7
         */
        $f = filter_var($request->query->get('f'), FILTER_SANITIZE_STRING);
        if (!empty($f) && is_array($f) && !empty($f[0])) {
            switch ($f[0]) {
                case 'im_field_category:7':
                    return $this->redirectToRoute('frameworks_list_by_category', ['category' => 'technology-products-services']);
                case 'im_field_category:14':
                    return $this->redirectToRoute('frameworks_list_by_category', ['category' => 'professional-services']);
                case 'im_field_category:20':
                    return $this->redirectToRoute('frameworks_list_by_pillar', ['pillar' => 'buildings']);
                case 'im_field_category:9':
                    return $this->redirectToRoute('frameworks_list_by_category', ['category' => 'utilities-fuels']);
                case 'im_field_category:10':
                    return $this->redirectToRoute('frameworks_list_by_category', ['category' => 'marcomms-research']);
                case 'im_field_category:19':
                    return $this->redirectToRoute('frameworks_list_by_category', ['category' => 'workplace']);
                case 'im_field_category:21':
                    return $this->redirectToRoute('frameworks_list_by_pillar', ['pillar' => 'technology']);
                case 'im_field_category:16':
                    return $this->redirectToRoute('frameworks_list_by_category', ['category' => 'fleet']);
                case 'im_field_category:15':
                    return $this->redirectToRoute('frameworks_list_by_category', ['category' => 'travel']);
                case 'im_field_category:41':
                    return $this->redirectToRoute('frameworks_list_by_category', ['category' => 'construction']);
            }
            if (preg_match('/^im_field_category/', $f[0])) {
                return $this->redirectToRoute('frameworks_list');
            }
        }

        /**
         * Detect incoming old links from ccs-agreements
         * E.g. ?sm_field_contract_id=RM3823*
         * ?sm_field_contract_id="RM3823:10a"
         */
        $smField = filter_var($request->query->get('sm_field_contract_id'), FILTER_SANITIZE_STRING);
        if (!empty($smField)) {
            $smField = filter_var($smField, FILTER_SANITIZE_STRING);
            $smField = html_entity_decode($smField);
            $smField = preg_replace('![^a-zA-Z0-9./\-:]!', '', $smField);

            $elements = explode(':', $smField);
            if (count($elements) === 1) {
                return $this->redirectToRoute('frameworks_suppliers', ['rmNumber' => $elements[0]]);
            } else {
                return $this->redirectToRoute('frameworks_lot_suppliers', ['rmNumber' => $elements[0], 'lotNumber' => $elements[1]]);
            }
        }

        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        $this->searchApi->setCacheKey($request->getRequestUri());

        // We are overriding the content model here
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        $limit = $request->query->has('limit') ? (int) filter_var($request->query->get('limit'), FILTER_SANITIZE_NUMBER_INT) : 20;

        try {
            $results = $this->searchApi->list($page, ['limit' => $limit]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        $data = [
            'pagination' => $results->getPagination(),
            'results'    => $results,
            'categories' => FrameworkCategories::getAll(),
            'pillars'       => FrameworkCategories::getAllPillars()
        ];

        return $this->render('frameworks/list.html.twig', $data);
    }

    /**
     * List upcoming deals
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentFieldNotSetException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function upcomingDeals(Request $request)
    {
        $this->api->setContentType('upcoming_deals');
        $this->api->setCacheKey($request->getRequestUri());

        // @todo At present need to pass fake ID since API method is intended to return one item with an ID, review this
        try {
            $results = $this->api->getOne(0);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

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
     * @param Request $request
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
    public function listByCategory(Request $request, string $category, int $page = 1)
    {
        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);
        $category = filter_var($category, FILTER_SANITIZE_STRING);

        // Map category slug to category db value
        $categoryName = FrameworkCategories::getDbValueBySlug($category);
        if ($categoryName === null) {
            $this->redirectToRoute('frameworks_list');
        }

        $this->searchApi->setCacheKey($request->getRequestUri());

        // We are overriding the content model here
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        try {
            $results = $this->searchApi->list($page, [
                'category' => $categoryName,
                'limit' => 20

            ]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

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
     * @param Request $request
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
    public function listByPillar(Request $request, string $pillar, int $page = 1)
    {
        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);
        $pillar = filter_var($pillar, FILTER_SANITIZE_STRING);

        // Map category slug to category db value
        $pillarName = FrameworkCategories::getDbValueBySlug($pillar);
        if ($pillarName === null) {
            $this->redirectToRoute('frameworks_list');
        }

        $this->searchApi->setCacheKey($request->getRequestUri());

        // We are overriding the content model here
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        try {
            $results = $this->searchApi->list($page, [
                'pillar' => $pillarName,
                'limit' => 20
            ]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

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
     * Search frameworks
     *
     * @see http://ccs-agreements.cabinetoffice.localhost/wp-json/ccs/v1/frameworks/?keyword=RM6107
     * @see http://ccs-agreements.cabinetoffice.localhost/wp-json/ccs/v1/frameworks/?keyword=Courier%20Services
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
        // Get feature flag if it exists &feature=guidedmatch
        $flag = filter_var($request->query->get('feature'), FILTER_SANITIZE_STRING);


        // Get search query
        $query =  filter_var($request->query->get('q'), FILTER_SANITIZE_STRING);
        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        $this->searchApi->setCacheKey($request->getRequestUri());

        // We are overriding the content model here
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        $limit = $request->query->has('limit') ? (int) filter_var($request->query->get('limit'), FILTER_SANITIZE_NUMBER_INT) : 20;

        if ($request->query->has('status')) {
            $status = [];
            foreach ($request->query->get('status') as $item) {
                $status[] = filter_var($item, FILTER_SANITIZE_STRING);
            }
        }

        if ($request->query->has('category')) {
            $category = [];
            foreach ($request->query->get('category') as $item) {
                $category[] = filter_var($item, FILTER_SANITIZE_STRING);
            }
        }

        try {
            $results = $this->searchApi->list($page, [
                'keyword'   => $query,
                'limit'     => $limit,
                'status'    => $status ?? null,
                'pillar'    => $category ?? null,
            ]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e) ;
        }
        if ($flag == 'guidedmatch'){
            $data = [
                'query'                      => $query,
                'pagination'                 => $results->getPagination(),
                'results'                    => $results,
                'categories'                 => FrameworkCategories::getAll(),
                'pillars'                    => FrameworkCategories::getAllPillars(),
                'guided_match_flag'          => $flag,
                'keywords'                   => ['linen', 'laptop', 'legal'],
                'match_url'                  => getenv('GUIDED_MATCH_URL') . $query
            ];
        } else {
            $data = [
                'query'         => $query,
                'pagination'    => $results->getPagination(),
                'results'       => $results,
                'categories'    => FrameworkCategories::getAll(),
                'pillars'       => FrameworkCategories::getAllPillars()
            ];
        }
        return $this->render('frameworks/list.html.twig', $data);
    }

    /**
     * Show one framework
     *
     * @param string $rmNumber
     * @param Request $request
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
        $rmNumber = filter_var($rmNumber, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $results = $this->api->getOne($rmNumber);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Framework agreement not found', $e);
        }

        $data = [
            'framework' => $results
        ];
        return $this->render('frameworks/show.html.twig', $data);
    }


    /**
     * List unique suppliers on a framework
     *
     * @param Request $request
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
    public function suppliersOnFramework(Request $request, string $rmNumber, int $page = 1)
    {
        $rmNumber = filter_var($rmNumber, FILTER_SANITIZE_STRING);
        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        // Set custom API endpoint
        // @todo Find better way to set custom endpoint URLs
        $this->api->getContentModel()->getContentType('framework_suppliers')->setApiEndpoint(sprintf('ccs/v1/framework-suppliers/%s', $rmNumber));
        $this->api->setContentType('framework_suppliers');

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $results = $this->api->list($page, ['limit' => 20]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

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
     * @param Request $request
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
    public function suppliersOnLot(Request $request, string $rmNumber, string $lotNumber, int $page = 1)
    {
        $rmNumber = filter_var($rmNumber, FILTER_SANITIZE_STRING);
        $lotNumber = filter_var($lotNumber, FILTER_SANITIZE_STRING);
        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        $this->api->getContentModel()->getContentType('framework_lot_suppliers')->setApiEndpoint(sprintf('ccs/v1/lot-suppliers/%s/lot/%s', $rmNumber, $lotNumber));
        $this->api->setContentType('framework_lot_suppliers');

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $results = $this->api->list($page, ['limit' => 20]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        $data = [
            'pagination'    => $results->getPagination(),
            'results'       => $results,
            'metadata'      => $results->getMetadata()
        ];

        return $this->render('frameworks/lot-suppliers.html.twig', $data);
    }


    public function suppliersOnLotCsv(Request $request, string $rmNumber, string $lotNumber)
    {

        $rmNumber = filter_var($rmNumber, FILTER_SANITIZE_STRING);
        $lotNumber = filter_var($lotNumber, FILTER_SANITIZE_STRING);


        $this->api->getContentModel()->getContentType('framework_lot_suppliers')->setApiEndpoint(sprintf('ccs/v1/lot-suppliers/%s/lot/%s', $rmNumber, $lotNumber));
        $this->api->setContentType('framework_lot_suppliers');
        $this->api->setCacheKey($request->getRequestUri());

        $csvData = array(
            0 => [
            'Supplier Name',
            'Contact Name',
            'Contact Email',
            'Street',
            'City',
            'Postcode',
            ]
        );

        // Iterate through suppliers and store necessary information into CSV array.

        try {
            $results = $this->api->list(1, ['limit' => 10000]);

            $i = 1;
            foreach ($results as $item) {
                $supplier_name = ($item->getContent()->get('supplier_name')) ? $item->getContent()->get('supplier_name')->getValue() : '';
                $contact_name = ($item->getContent()->get('supplier_contact_name')) ? $item->getContent()->get('supplier_contact_name')->getValue() : '';
                $contact_email = ($item->getContent()->get('supplier_contact_email')) ? $item->getContent()->get('supplier_contact_email')->getValue() : '';
                $street = ($item->getContent()->get('supplier_street')) ? $item->getContent()->get('supplier_street')->getValue() : '';
                $city = ($item->getContent()->get('supplier_city')) ? $item->getContent()->get('supplier_city')->getValue() : '';
                $postcode = ($item->getContent()->get('supplier_postcode')) ? $item->getContent()->get('supplier_postcode')->getValue() : '';

                $csvData[$i][] = $supplier_name;
                $csvData[$i][] = $contact_name;
                $csvData[$i][] = $contact_email;
                $csvData[$i][] = $street;
                $csvData[$i][] = $city;
                $csvData[$i][] = $postcode;

                $i++;
            }
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        // Process array into CSV using memory handle (easiest way to pass it into a variable)

        $csv = fopen('php://temp/maxmemory:' . (5 * 1024 * 1024), 'r+');

        if ($csvData) {
            foreach ($csvData as $row) {
                fputcsv($csv, $row);
            }
        }

        rewind($csv);
        $output = stream_get_contents($csv);

        // Output the CSV

        $response = new Response();
        $response->setContent($output);

        $response->headers->set('Content-Type', 'text/plain');

        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="' . $rmNumber . '_Lot' . $lotNumber . '.csv' . '";'
        );

        return $response;
    }
}
