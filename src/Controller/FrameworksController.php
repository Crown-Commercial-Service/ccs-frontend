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
        $f = filter_var($request->query->get('f'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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
        $smField = filter_var($request->query->get('sm_field_contract_id'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        if (!empty($smField)) {
            $smField = filter_var($smField, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $smField = html_entity_decode($smField);
            $smField = preg_replace('![^a-zA-Z0-9./\-:]!', '', $smField);

            $elements = explode(':', (string) $smField);
            if (count($elements) === 1) {
                return $this->redirectToRoute('frameworks_suppliers', ['rmNumber' => $elements[0]]);
            } else {
                return $this->redirectToRoute('frameworks_lot_suppliers', ['rmNumber' => $elements[0], 'lotNumber' => $elements[1]]);
            }
        }

        // check if user is bot and block the request
        $this->blockBot($request->headers->get('User-Agent'));

        $redirectForCatOrPillar  = $this->redirectToCatOrPillar($request);
        if ($redirectForCatOrPillar != null) {
            return $this->redirectToRoute('frameworks_list', $redirectForCatOrPillar);
        }

        $query = $this->sanitizeSearchQuery($request->query->get('keyword'));
        $page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        $this->searchApi->setCacheKey($request->getRequestUri());
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        $limit = $request->query->has('limit') ? (int) filter_var($request->query->get('limit'), FILTER_SANITIZE_NUMBER_INT) : 20;


        if (!is_array($request->query->get('status'))) {
            $checkedStatusArray         = $request->query->get('status') != null ? explode(",", $request->query->get('status')) : ["Live"];
        } else {
            $checkedStatusArray = $request->query->get('status');
        }

        $checkedRegulationArray     = ControllerHelper::getArrayFromStringForParam($request, "regulation", "allRegulation");
        $checkedTypeArray           = ControllerHelper::getArrayFromStringForParam($request, "type", "allType");

        //if allPillarAndCategory is checked || all Pillars are checked || all Categories are checked
        if ($request->query->get("allPillarAndCategory", false) || count((array) $request->query->get("pillar", [])) == FrameworkCategories::getAllPillarSize() || count((array) $request->query->get("category", [])) == FrameworkCategories::getAllCategorySize()) {
            $checkedPillarArray   = [];
            $checkedCategoryArray = [];
        } else {
            $checkedPillarArray         = ControllerHelper::getArrayFromStringForParam($request, "pillar");
            $pillarsAndCategories       = ControllerHelper::validateCategory($request, $checkedPillarArray, "category");
            $checkedCategoryArray       = $pillarsAndCategories[0];
            $checkedPillarArray         = $pillarsAndCategories[1];
        }


        $options = [
            "keyword"                     => $query,
            "checkedStatus"               => $checkedStatusArray,
            "checkedRegulation"           => $checkedRegulationArray,
            "checkedType"                 => $checkedTypeArray,
            "checkedPillar"               => $checkedPillarArray,
            "checkedCategory"             => $checkedCategoryArray,
        ];

        try {
            $results = $this->searchApi->list($page, [
                'keyword'           => $options['keyword'],
                'status'            => $options['checkedStatus'],
                'regulation'        => $options['checkedRegulation'],
                'regulation_type'   => $options['checkedType'],
                'pillar'            => $options['checkedPillar'],
                'category'          => $options['checkedCategory'],
                'limit'             => $limit,
            ]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        $data = [
            'api_base_url'      => getenv('SEARCH_API_BASE_URL'),
            'app_base_url'      => getenv('APP_BASE_URL'),
            'pageNumber'        => $page,
            'filters'           => $options,
            'pagination'        => $results->getPagination(),
            'results'           => $results,
            'categories'        => FrameworkCategories::getAll(),
            'pillars'           => FrameworkCategories::getAllPillars(),
            'pillarsAndCategories' => $this->getPillarsAndCategories(),
            'statuses'          => ["live"],
            'regulation'        => ['PA2023', 'PCR2015', 'PCR2006'],
            'regulationType'    => ["Dynamic+Purchasing+System", "Dynamic+Market", "Open+Framework", "Closed+Framework", "PCR15+Framework", "PCR06+Framework"],
        ];

        return $this->render('frameworks/list.html.twig', $data);
    }

    private function getPillarsAndCategories()
    {
        $pillarsAndCategories = [];
        $pillars = FrameworkCategories::getAllPillars();
        foreach ($pillars['pillars'] as $pillar) {
            $categories = FrameworkCategories::getAllByPillar($pillar['name']);
            $pillarsAndCategories[$pillar['slug']] = $categories;
        }
        return $pillarsAndCategories;
    }

    public function upcomingDealsSearch(Request $request)
    {
        $query = $this->sanitizeSearchQuery($request->query->get('keyword'));
        $page = 1;

        $this->searchApi->setCacheKey($request->getRequestUri());
        $this->searchApi->getContentType()->setApiEndpoint('frameworks');

        $limit = $request->query->has('limit') ? (int) filter_var($request->query->get('limit'), FILTER_SANITIZE_NUMBER_INT) : 110;

        $checkedTypeArray = $this->getCheckedTypeArray($request);

        $options = [
            "keyword"                     => $query,
            "checkedStatus"               => ["Upcoming", "Live"],
            "checkedType"                 => $checkedTypeArray,
        ];

        try {
            $results = $this->searchApi->list($page, [
                'keyword'           => $options['keyword'],
                'status'            => $options['checkedStatus'],
                'limit'             => $limit,
                'regulation_type'    => $options["checkedType"],
            ]);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        $upcomingDealsContent = $this->getUpcomingDealsInfo();

        $cscMessage = ControllerHelper::getCSCMessage();

        $statuses = (array) $request->query->get('statuses', ['all']);
        $checkedTypes = (array) $request->query->get('type', ['allType']);

        if (count($statuses) === 5 || in_array('all', $statuses)) {
            $statuses = ['all'];
        }

        if (!$checkedTypes || count($checkedTypes) === 5 || in_array('allType', $checkedTypes)) {
            $checkedTypes = ["allType"];
        }

        $data = [
            'statuses'                      => $statuses,
            'filters'                       => $options,
            'results'                       => $results,
            'type'                          => $checkedTypes,
            'upcoming_deals_content'        => $upcomingDealsContent,
            'cscMessage'                    => $cscMessage,
        ];

        return $this->render('frameworks/upcoming-list.html.twig', $data);
    }

    private function sanitizeSearchQuery($keyword)
    {
        $originalSearch = str_replace('/', '', strip_tags(html_entity_decode((string) $keyword)));
        return preg_replace("/[^a-zA-Z0-9\s]/", "", $originalSearch);
    }

    private function getCheckedTypeArray(Request $request)
    {
        $checkedTypeArray = ControllerHelper::getArrayFromStringForParam($request, "type");

        return (in_array("allType", $checkedTypeArray) || count($checkedTypeArray) === 5)
            ? []
            : $checkedTypeArray;
    }

    private function getUpcomingDealsInfo()
    {
        $url = getenv('APP_API_BASE_URL') . 'ccs/v1/upcoming-deals-page/0';

        $client = HttpClient::create();
        $response = $client->request(
            'GET',
            $url,
        );

        if ($response->getStatusCode() == 200) {
            $upcomingDealsContent = json_decode($response->getContent());
        }

        return $upcomingDealsContent;
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
        $rmNumber = filter_var($rmNumber, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ($rmNumber == "RM6187_cas") {
            return $this->redirectToRoute('frameworks_show', ["rmNumber" => "RM6187"]);
        }

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $results = $this->api->getOne($rmNumber);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Framework agreement not found', $e);
        }

        $results = $this->setGovTableStyleForAllField($results);

        $content_group = "agreement/" .  ControllerHelper::toSlug($results->getContent()["category"]->getValue());
        $cscMessage = ControllerHelper::getCSCMessage();

        $data = [
            'framework'          => $results,
            'cscMessage'         => $cscMessage,
            'content_group'      => $content_group,
            'youtubeVideo'       => ControllerHelper::getYoutubeVideo()
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
        $rmNumber = filter_var($rmNumber, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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
        $rmNumber = filter_var($rmNumber, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $lotNumber = filter_var($lotNumber, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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

        $rmNumber = filter_var($rmNumber, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $lotNumber = filter_var($lotNumber, FILTER_SANITIZE_FULL_SPECIAL_CHARS);


        $this->api->getContentModel()->getContentType('framework_lot_suppliers')->setApiEndpoint(sprintf('ccs/v1/lot-suppliers/%s/lot/%s', $rmNumber, $lotNumber));
        $this->api->setContentType('framework_lot_suppliers');
        $this->api->setCacheKey($request->getRequestUri());

        $csvData = [
            0 => [
            'Supplier Name',
            'Contact Name',
            'Contact Email',
            'Street',
            'City',
            'Postcode',
            ]
        ];

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
        if (str_contains((string) $input, '<td') or str_contains((string) $input, '<tr') or str_contains((string) $input, '<table') or str_contains((string) $input, '<th')) {
            return (true);
        }

        return(false);
    }

    private function blockBot(string $userAgent)
    {
        if (preg_match('/bot|crawl|slurp|spider/i', $userAgent)) {
            die();
        }
    }

    private function redirectToCatOrPillar($request)
    {
        $oldCategory    = $request->attributes->get('category', null);

        $redirectToCat = [
            "utilities-fuels"   => "Energy",
            "software-cyber"    => "Software and Hardware",
            "office-and-travel" => "Travel, Accommodation and Venues",
            "travel"            => "Travel, Accommodation and Venues",
            "digital-future"    => "Digital Capability and Delivery",
            "digital-specialists"                           => "Digital Capability and Delivery",
            "network-solutions"                             => "Network Services",
            "technology-solutions-outcomes"                 => "Software and Hardware",
            "document-management-logistics"                 => "Estates Support Services",
            "marcomms-research"                             => "Professional Services",
            "travel-transport-accommodation-and-venues"     => "Travel, Accommodation and Venues",
            "psr-permanent-recruitment"                     => "HR and Workforce Services",
            "workforce-health-education"                    => "HR and Workforce Services",
            "people-services"                               => "HR and Workforce Services",
            "estate-support-services"                       => "Facilities Management",
            "technology-services"                           => "Digital and Technology Services",
            "digital-capability-and-delivery"               => "Digital and Technology Services",
            "software-and-hardware"                         => "Software",
        ];

        $redirectToPillar = [
            "workplace"                     => "Estates",
            "technology-products-services"  => "Technology",
        ];

        if (isset($oldCategory) && array_key_exists($oldCategory, (array)$redirectToCat)) {
            return ['category' => [$redirectToCat[$oldCategory]]];
        } elseif (isset($oldCategory) && array_key_exists($oldCategory, (array)$redirectToPillar)) {
            return ['pillar' => [$redirectToPillar[$oldCategory]]];
        }
        return null;
    }
}
