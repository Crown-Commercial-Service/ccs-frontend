<?php

namespace App\Controller;

use App\Utils\FrameworkCategories;
use App\Helper\ControllerHelper;
use Symfony\Component\Cache\Psr16Cache;
use Psr\Cache\CacheItemPoolInterface;
use Strata\Frontend\ContentModel\ContentModel;
use Strata\Frontend\Exception\PaginationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Strata\Frontend\Cms\RestData;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Strata\Frontend\Exception\NotFoundException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Rollbar\Rollbar;
use Rollbar\Payload\Level;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Exception;

class FrameworksController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var RestData
     */
    protected $api;

    /**
     * @var \Strata\Frontend\Cms\RestData
     */
    protected $searchApi;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('frameworks');
        $psr16Cache = new Psr16Cache($cache);
        $this->api->setCache($psr16Cache);
        $this->api->setCacheLifetime(900);

        $this->searchApi = new RestData(
            getenv('SEARCH_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );

        $this->searchApi->setContentType('frameworks');
        $this->searchApi->setCache($psr16Cache);
        $this->searchApi->setCacheLifetime(1);
    }

    /**
     * List frameworks
     *
     * @param Request $request
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PaginationException
     * @throws \Strata\Frontend\Exception\PermissionException
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
                    return $this->redirectToRoute('frameworks_list_by_category', ['category' => 'energy']);
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
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        $limit = $request->query->has('limit') ? (int) filter_var($request->query->get('limit'), FILTER_SANITIZE_NUMBER_INT) : 20;

        $checkedStatusArray = $request->query->get('status') != null ? explode(",", $request->query->get('status')) : ["Live"];

        $options = [
            "checkedStatus"               => $checkedStatusArray,
            "checkedRegulation"           => [],
            "checkedType"                 => [],
            "checkedPillar"               => [],
            "checkedCategory"             => [],
        ];



        try {
            $results = $this->searchApi->list($page, ['limit' => $limit]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        $data = [
            'api_base_url'          => getenv('SEARCH_API_BASE_URL'),
            'app_base_url'          => getenv('APP_BASE_URL'),
            'pageNumber'            => $page,
            'filters'               => $options,

            'tpp_feature_toggle' => getenv('TPP_feature_toggle'),
            'pagination'        => $results->getPagination(),
            'results'           => $results,
            'categories'        => FrameworkCategories::getAll(),
            'pillars'           => FrameworkCategories::getAllPillars(),
            'statuses'          => ["live"],
            'regulation'        => ["allRegulation"],
            'regulationType'    => ["allType"],
        ];

        if (getenv('APP_ENV') == 'prod' || getenv('APP_ENV') == 'test') {
            return $this->render('frameworks/list.html.twig', $data);
        } else {
            return $this->render('frameworks/list_with_vue.html.twig', $data);
        }
    }

    /**
     * List upcoming deals
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentFieldNotSetException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PermissionException
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

        // request to upcoming deals information api for titles and description
        $upcomingDealsUrl = getenv('APP_API_BASE_URL') . 'ccs/v1/upcoming-deals-page/0';

        $client = HttpClient::create();
        $response = $client->request(
            'GET',
            $upcomingDealsUrl,
        );

        $upcomingDealsContent = null;

        $cscMessage = ControllerHelper::getCSCMessage();

        if ($response->getStatusCode() == 200) {
            $upcomingDealsContent = json_decode($response->getContent());
        }

        $data = [
            'tpp_feature_toggle'            => getenv('TPP_feature_toggle'),
            'awarded_pipeline'              => $results->getContent()->get('awarded_pipeline'),
            'underway_pipeline'             => $results->getContent()->get('underway_pipeline'),
            'dynamic_purchasing_systems'    => $results->getContent()->get('dynamic_purchasing_systems'),
            'planned_pipeline'              => $results->getContent()->get('planned_pipeline'),
            'future_pipeline'               => $results->getContent()->get('future_pipeline'),
            'upcoming_deals_content'        => $upcomingDealsContent,
            'cscMessage'                    => $cscMessage,
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
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PaginationException
     * @throws \Strata\Frontend\Exception\PermissionException
     */
    public function listByCategory(Request $request, string $category, string $query, int $page = 1)
    {
        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);
        $category = filter_var($category, FILTER_SANITIZE_STRING);
        $query = filter_var($query, FILTER_SANITIZE_STRING);
        $statuses = $this->getAgreementFilterStatusArray($request);

        $redirectToCat = array(
            "utilities-fuels"   => "energy",
            "software-cyber"    => "software-and-hardware",
            "office-and-travel" => "travel-accommodation-and-Venues",
            "travel"            => "travel-accommodation-and-Venues",
            "digital-future"    => "digital-capability-and-delivery",
            "digital-specialists"                           => "digital-capability-and-delivery",
            "network-solutions"                             => "network-services",
            "technology-solutions-outcomes"                 => "software-and-hardware",
            "document-management-logistics"                 => "estates-support-services",
            "marcomms-research"                             => "professional-services",
            "travel-transport-accommodation-and-venues"     => "travel-accommodation-and-Venues",
            "psr-permanent-recruitment"                     => "hr-and-workforce-services",
            "workforce-health-education"                    => "hr-and-workforce-services",
            "people-services"                               => "hr-and-workforce-services",
        );

        $redirectToPillar = array(
            "workplace"                     => "estates",
            "technology-products-services"  => "technology",
        );


        if (array_key_exists($category, $redirectToCat)) {
            return $this->redirectToRoute('frameworks_list_by_category', ['category' => $redirectToCat[$category]]);
        } elseif (array_key_exists($category, $redirectToPillar)) {
            return $this->redirectToRoute('frameworks_list_by_pillar', ['pillar' => $redirectToPillar[$category]]);
        }

        $categoryName = FrameworkCategories::getDbValueBySlug($category);
        if ($categoryName === null) {
            $this->redirectToRoute('frameworks_list');
        }
        $this->searchApi->setCacheKey($request->getRequestUri());
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        $RegulationAndType   = $this->getRegulationAndType($request);
        $regulationFilter   = $RegulationAndType[0];
        $typeFilter         = $RegulationAndType[1];

        try {
            $results = $this->searchApi->list($page, [
                'keyword'   => (!empty($query) && trim($query) != '' ? $query : null),
                'category' => $categoryName,
                'limit' => 20,
                'status' => $statuses,
                'regulation'        => $this->removeViewAllForAPI($regulationFilter),
                'regulation_type'   => $this->removeViewAllForAPI($typeFilter)
            ]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        $data = [
            'tpp_feature_toggle' => getenv('TPP_feature_toggle'),
            'query'         => $query,
            'match_url'     => getenv('GUIDED_MATCH_URL') . rawurlencode($query),
            'category'      => $categoryName,
            'category_slug' => $category,
            'pagination'    => $results->getPagination(),
            'results'       => $results,
            'categories'    => FrameworkCategories::getAll(),
            'pillars'       => FrameworkCategories::getAllPillars(),
            'statuses'      => $statuses,
            'regulation'    => $regulationFilter,
            'regulationType' => $typeFilter,
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
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PaginationException
     * @throws \Strata\Frontend\Exception\PermissionException
     */
    public function listByPillar(Request $request, string $pillar, string $query, int $page = 1)
    {
        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);
        $pillar = filter_var($pillar, FILTER_SANITIZE_STRING);
        $query = filter_var($query, FILTER_SANITIZE_STRING);
        $statuses = $this->getAgreementFilterStatusArray($request);

        $pillarName = FrameworkCategories::getDbValueBySlug($pillar);
        if ($pillarName === null) {
            $this->redirectToRoute('frameworks_list');
        }

        $this->searchApi->setCacheKey($request->getRequestUri());
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        $RegulationAndType   = $this->getRegulationAndType($request);
        $regulationFilter   = $RegulationAndType[0];
        $typeFilter         = $RegulationAndType[1];

        try {
            $results = $this->searchApi->list($page, [
                'keyword'   => (!empty($query) && trim($query) != '' ? $query : null),
                'pillar' => $pillarName,
                'limit' => 20,
                'status' => $statuses,
                'regulation'        => $this->removeViewAllForAPI($regulationFilter),
                'regulation_type'   => $this->removeViewAllForAPI($typeFilter)
            ]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        $data = [
            'tpp_feature_toggle' => getenv('TPP_feature_toggle'),
            'query'         => $query,
            'match_url'     => getenv('GUIDED_MATCH_URL') . rawurlencode($query),
            'pillar'        => $pillarName,
            'pillar_slug'   => $pillar,
            'pagination'    => $results->getPagination(),
            'results'       => $results,
            'categories'    => FrameworkCategories::getAll(),
            'pillars'       => FrameworkCategories::getAllPillars(),
            'statuses'      => $statuses,
            'regulation'    => $regulationFilter,
            'regulationType' => $typeFilter,
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
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PaginationException
     * @throws \Strata\Frontend\Exception\PermissionException
     */
    public function search(Request $request, int $page = 1)
    {
        // check if user is bot and block the request
        $this->blockBot($request->headers->get('User-Agent'));

        // Get search query
        // strip special characters and tags from search query
        $orginalSearch = str_replace('/', '', strip_tags(html_entity_decode($request->query->get('q'))));
        $query = preg_replace("/[^a-zA-Z0-9\s]/", "", $orginalSearch);
        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        $this->searchApi->setCacheKey($request->getRequestUri());
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        $limit = $request->query->has('limit') ? (int) filter_var($request->query->get('limit'), FILTER_SANITIZE_NUMBER_INT) : 20;

        $statuses = $this->getAgreementFilterStatusArray($request);

        $category =  filter_var($request->query->get('category'), FILTER_SANITIZE_STRING);
        $pillar =  filter_var($request->query->get('pillar'), FILTER_SANITIZE_STRING);
        $categoryName = $this-> getPillarOrCategoryName($request, 'category');
        $pillarName = $this-> getPillarOrCategoryName($request, 'pillar');

        $RegulationAndType   = $this->getRegulationAndType($request);
        $regulationFilter   = $RegulationAndType[0];
        $typeFilter         = $RegulationAndType[1];

        try {
            $results = $this->searchApi->list($page, [
                'keyword'   => (!empty($query) && trim($query) != '' ? $query : null),
                'limit'     => $limit,
                'category'  => $categoryName ?? null,
                'pillar'    => $pillarName ?? null,
                'status'    => $statuses,
                'regulation'        => $this->removeViewAllForAPI($regulationFilter),
                'regulation_type'   => $this->removeViewAllForAPI($typeFilter)
            ]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        $data = [
            'tpp_feature_toggle' => getenv('TPP_feature_toggle'),
            'query'         => $query,
            'pagination'    => $results->getPagination(),
            'results'       => $results,
            'categories'    => FrameworkCategories::getAll(),
            'pillars'       => FrameworkCategories::getAllPillars(),
            'category'      => (!empty($categoryName) ? $categoryName : null),
            'category_slug' => (!empty($category) ? $category : null),
            'pillar'        => (!empty($pillarName) ? $pillarName : null),
            'pillar_slug'   => (!empty($pillar) ? $pillar : null),
            'match_url'     => getenv('GUIDED_MATCH_URL') . rawurlencode($orginalSearch),
            'statuses'      => $statuses,
            'regulation'    => $regulationFilter,
            'regulationType' => $typeFilter,
        ];

        return $this->render('frameworks/list.html.twig', $data);
    }

    /**
     * Show one framework
     *
     * @param string $rmNumber
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentFieldNotSetException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PermissionException
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

        $results = $this->setGovTableStyleForAllField($results);

        $content = $results->getContent();
        $content_group = "agreement/" .  ControllerHelper::toSlug($results->getContent()["category"]->getValue());
        $cscMessage = ControllerHelper::getCSCMessage();

        $youtubeVideo = ControllerHelper::getYoutubeVideo();

        $data = [
            'tpp_feature_toggle' => getenv('TPP_feature_toggle'),
            'framework'          => $results,
            'show_crp'           => $this->showCRP($content['rm_number']->getValue()),
            'cscMessage'         => $cscMessage,
            'content_group'      => $content_group,
            'youtubeVideo'       => $youtubeVideo
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
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PaginationException
     * @throws \Strata\Frontend\Exception\PermissionException
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
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentFieldNotSetException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PaginationException
     * @throws \Strata\Frontend\Exception\PermissionException
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

    private function getPillarOrCategoryName(Request $request, string $PillarOrCategory)
    {

        if ($request->query->has($PillarOrCategory)) {
            $category = filter_var($request->query->get($PillarOrCategory), FILTER_SANITIZE_STRING);
            $categoryName = FrameworkCategories::getDbValueBySlug($category);
            return $categoryName;
        }

        return null;
    }

    private function setGovTableStyleForAllField($results)
    {
        $description = $results->getContent()['description']->getValue();
        if ($description != "") {
            $description = $this->addGovUkClassToTables($description);
            $results->getContent()['description']->setContent($description);
        }

        $benefits = $results->getContent()['benefits']->getValue();
        if ($benefits != "") {
            $benefits = $this->addGovUkClassToTables($benefits);
            $results->getContent()['benefits']->setContent($benefits);
        }

        $howToBuy = $results->getContent()['how_to_buy']->getValue();
        if ($howToBuy != "") {
            $howToBuy = $this->addGovUkClassToTables($howToBuy);
            $results->getContent()['how_to_buy']->setContent($howToBuy);
        }

        $upcomingDealDetails = $results->getContent()['upcoming_deal_details']->getValue();
        if ($upcomingDealDetails != "") {
            $upcomingDealDetails = $this->addGovUkClassToTables($upcomingDealDetails);
            $results->getContent()['upcoming_deal_details']->setContent($upcomingDealDetails);
        }

        return $results;
    }

    private function addGovUkClassToTables(string $input)
    {
        $lines = explode(PHP_EOL, $input);
        $output = [];

        foreach ($lines as $eachLine) {
            if ($this->stringContainTableElement($eachLine)) {
                $eachLine = str_replace("<table", "<table class=\"govuk-table\" ", $eachLine);
                $eachLine = str_replace("<tr", "<tr class=\"govuk-table__row\" ", $eachLine);
                $eachLine = str_replace("<td", "<td class=\"govuk-table__cell\" ", $eachLine);
                $eachLine = str_replace("<th", "<th class=\"govuk-table__header\" ", $eachLine);
            }
            $output[] = $eachLine;
        }
        return implode("\n", $output);
    }

    private function stringContainTableElement($input)
    {
        if (strpos($input, '<td') !== false or strpos($input, '<tr') !== false or strpos($input, '<table') !== false or strpos($input, '<th') !== false) {
            return (true);
        }

        return(false);
    }

    private function showCRP($rm_number)
    {
        $CRP_EXCLUDED_FRAMEWORKS = array('RM6269', 'RM6263', 'RM6282', 'RM6186', 'RM6195', 'RM6232', 'RM6248', 'RM6257');
        return !in_array($rm_number, $CRP_EXCLUDED_FRAMEWORKS);
    }

    private function blockBot(string $userAgent)
    {
        if (preg_match('/bot|crawl|slurp|spider/i', $userAgent)) {
            die();
        }
    }

    private function getAgreementFilterStatusArray(Request $request)
    {
        return (array) $request->query->get('statuses', ["live"]);
    }

    private function getRegulationAndType(Request $request)
    {
        $regulationFilter   = (array) $request->query->get("regulation", []);
        $typeFilter         = (array) $request->query->get("regulationType", []);

        if (count($regulationFilter) == 3 || count($regulationFilter) == 0 || in_array('allRegulation', $regulationFilter)) {
            $regulationFilter =  ['allRegulation'];
        }

        $typeFilter = $this->removeTypeFromRegulation($regulationFilter, $typeFilter);

        return [$regulationFilter, $typeFilter];
    }

    private function removeTypeFromRegulation($regulationFilter, $typeFilter)
    {
        $maxTypeFilterCount = 6;
        $notAllowRegulation = array();
        $regulationAndType = array(
            "PA2023"    => array("Closed Framework", "Dynamic Market", "Open Framework"),
            "PCR2015"   => array("Dynamic Purchasing System", "PCR15 Framework"),
            "PCR2006"   => array("PCR06 Framework"),
        );

        foreach ($regulationAndType as $eachRegulation => $data) {
            if (!in_array($eachRegulation, $regulationFilter)) {
                $notAllowRegulation = array_merge($notAllowRegulation, $data);
            } else {
                $maxTypeFilterCount += sizeof($data);
            }
        }

        if (sizeof($notAllowRegulation) != 6) {
            $typeFilter = ControllerHelper::removeFromArray($typeFilter, $notAllowRegulation);
        }

        if (count($typeFilter) == $maxTypeFilterCount || count($typeFilter) == 0 || in_array('allType', $typeFilter)) {
            $typeFilter =  ['allType'];
        }

        return $typeFilter;
    }

    private function removeViewAllForAPI(array $arrayToRemove)
    {

        $arrayToRemove = ControllerHelper::removeFromArray($arrayToRemove, ["allRegulation","allType", "true"]);

        return !$arrayToRemove ? null : $arrayToRemove;
    }
}
