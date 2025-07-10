<?php

declare(strict_types=1);

namespace App\Controller;

use App\Validation\FormValidation;
use App\Helper\ControllerHelper;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\Cache\Adapter\Psr16Adapter;
use Strata\Frontend\Cms\Wordpress;
use Strata\Frontend\Cms\RestData;
use Strata\Frontend\ContentModel\ContentModel;
use Strata\Frontend\Exception\FailedRequestException;
use Strata\Frontend\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class PageController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var Wordpress
     */
    protected $api;

    /**
     * Frameworks Rest API data
     *
     * @var RestData
     */
    protected $redirectionApi;

    /**
     * Frameworks Rest API data
     *
     * @var RestData
     */
    protected $glossaryApi;

    protected $client;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->api = new Wordpress(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $psr16Cache = new Psr16Cache($cache);
        $this->api->setContentType('page');
        $this->api->setCache($psr16Cache);
        $this->api->setCacheLifetime(900);
        $this->client = HttpClient::create();

        $this->redirectionApi = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->redirectionApi->setContentType('redirections');

        $this->glossaryApi = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->glossaryApi->setContentType('glossary');
    }

    /**
     * Homepage
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PaginationException
     * @throws \Strata\Frontend\Exception\PermissionException
     */
    public function home(Request $request)
    {
        $this->api->setCacheKey($request->getRequestUri());
        $flag = filter_var($request->query->get('feature'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $this->api->setContentType('news');
        $news = $this->api->listPages(1, ['limit' => 3]);

        // request to homepage components
        $homepageCompUrl = getenv('APP_API_BASE_URL') . 'ccs/v1/homepage-components/0';
        $messageBanner = ControllerHelper::getHomeMessageBanner();
        // dd($messageBanner);
        $client = HttpClient::create();
        $response = $client->request(
            'GET',
            $homepageCompUrl,
        );
        $homepageContent = null;

        if ($response->getStatusCode() == 200) {
            $homepageContent = json_decode($response->getContent());
        }

        return $this->render('pages/home.html.twig', [
            'news' => $news,
            'guided_match_flag' => $flag,
            'homepageContent' => $homepageContent,
            'messageBanner' => $messageBanner,

        ]);
    }

    /**
     * Generic page controller
     *
     * @param string $slug
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Strata\Frontend\Exception\ApiException
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PaginationException
     * @throws \Strata\Frontend\Exception\PermissionException
     */
    public function page(string $slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $redirectedLink = $this->checkRedirect($slug);

        if ($redirectedLink != '') {
            return $this->redirect($redirectedLink);
        }

        // @todo May need to look at mapping URLs to page IDs in the future
        try {
            $this->api->setCacheKey($request->getRequestUri());
            $page = $this->api->getPageByUrl($request->getRequestUri());
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        // Create breadcrumb
        // @todo Improve breadcrumb creation
        $parts = explode('/', trim($slug, '/'));
        array_pop($parts);
        $breadcrumb = [];
        $link = '';
        foreach ($parts as $part) {
            $name = ucfirst(str_replace('-', ' ', $part));
            $link .= '/' . $part;
            $breadcrumb[$link] = $name;
        }

        // request to option cards api
        $optionCardsUrl = getenv('APP_API_BASE_URL') . 'ccs/v1/option-cards/0';

        $response = $this->client->request(
            'GET',
            $optionCardsUrl,
        );

        $optionCardsContent = null;

        if ($response->getStatusCode() == 200) {
            $optionCardsContent = json_decode((string) $response->getContent());
        }

        $formErrors = null;
        $formData = $this->getFromData($request->request);
        $formCampaignCode = null;
        $featureNewsProperties = null;

        if (property_exists($page->getContent(), 'contact_form_form_campaign_code')) {
            $formCampaignCode = $page->getContent()['contact_form_form_campaign_code']->getValue();
        }

        if (array_key_exists('page_components_rows', (array) $page->getContent())) {
            foreach ($page->getContent()['page_components_rows']->getValue() as $acfFrield) {
                if ($acfFrield->getName() == 'feature_news_feature_news') {
                    $featureNewsProperties['newsType'] = property_exists($acfFrield->getContent(), 'feature_news_feature_news_news_type') 
                        ? $this->extractNewsPropertie($acfFrield->getContent()['feature_news_feature_news_news_type']) 
                        : null;

                    $featureNewsProperties['pAndSType'] = property_exists($acfFrield->getContent(), 'feature_news_feature_news_products_and_services') 
                        ? $this->extractNewsPropertie($acfFrield->getContent()['feature_news_feature_news_products_and_services']) 
                        : null;

                    $featureNewsProperties['sectorType'] = property_exists($acfFrield->getContent(), 'feature_news_feature_news_sectors') 
                        ? $this->extractNewsPropertie($acfFrield->getContent()['feature_news_feature_news_sectors']) 
                        : null;
                    break;
                }
            }
        }

        if ($request->isMethod('POST')) {
            $formErrors = $this->sendToSalesforceForPageEnquiry($request->request, $formData, $formCampaignCode);

            if ($formErrors instanceof Response) {
                return $formErrors;
            }
        }

        $cscMessage = ControllerHelper::getCSCMessage();
        $resourcesWithIndex = $this->extractResourcesFromContent($page->getContent());

        return $this->render('pages/page.html.twig', [
            'page'                       => $page,
            'breadcrumb_parents'         => $breadcrumb,
            'page_query_string'          => filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'query_string_type'          => isset($_GET['type']) ? filter_var($_GET['type'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null,
            'site_base_url'              => getenv('APP_BASE_URL'),
            'option_cards'               => $optionCardsContent,
            'slug'                       => $slug,
            'formErrors'                 => $formErrors,
            'formData'                   => $formData,
            'featureNewsProperties'      => $featureNewsProperties,
            'cscMessage'                 => $cscMessage,
            'resourcesWithIndex'         => $resourcesWithIndex
         ]);
    }

    private function extractResourcesFromContent($content)
    {
        $resources = [];
        $index = 1;
        if (property_exists($content, 'brochures_list_brochures_list')) {
                $resources['brochures_list_brochures_list'] = $index++;
            }
        if (property_exists($content, 'whitepapers_list_whitepapers')) {
            $resources['whitepapers_list_whitepapers'] = $index++;
        }
        if (property_exists($content, 'webinars_list_webinars')) {
            $resources['webinars_list_webinars'] = $index++;
        }
        if (property_exists($content, 'digital_brochures_list_digital_brochures')) {
            $resources['digital_brochures_list_digital_brochures'] = $index++;
        }
        if (property_exists($content, 'downloadable_list_downloadable_resource')) {
            $resources['downloadable_list_downloadable_resource'] = $index++;
        }

        return $resources;
    }

    private function checkRedirect($slug)
    {
        $slug = strtolower((string) $slug);

        try {
            // @todo At present need to pass fake ID since API method is intended to return one item with an ID, review this
            $results = $this->redirectionApi->getOne(0);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Redirection API broken', $e);
        }

        $listOfRedirection = $results->getContent()->get('results')->getValue();

        foreach ($listOfRedirection as $redirection) {
            $shortenUrl = $redirection->get('shortUrl')->getValue();
            $longUrl = getenv('APP_BASE_URL') . "/" . $redirection->get('longUrl')->getValue();

            if ($shortenUrl == $slug) {
                return $longUrl;
            }
        }

        return '';
    }

    private function sendToSalesforceForPageEnquiry($params, $formData, $formCampaignCode)
    {

        ControllerHelper::honeyPot($params->get('surname', null));

        $formErrors = $params->get('validateAggregationOption') ? $this->validateAggregationOptionForm($formData) : $this->validateForm($formData);

        if (!$formErrors) {
            $params->set('subject', $formCampaignCode);
            $params->set('00Nb0000009IXEW', $params->get('validateAggregationOption') ? $params->get('00Nb0000009IXEW') : $formCampaignCode);
            $params->set('recordType', '012b00000005NWC');
            $params->set('00Nb0000009IXEs', $formData['jobTitle']);
            $params->set('priority', 'Green');
            $params->set('orgid', ControllerHelper::getOrgId());

            $origin = $params->get('newsletterForm') ? 'Website - Newsletter' : 'Website - Page form enquiry';

            $params->set('origin', $origin);
            $params->set('description', $origin . ', callback: ' . $formData['callbackTimeslot'] . ', more-detail: ' . $formData['description']);

            $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                'query' => $params->all(),
            ]);

            if (!is_null($params->get('debug'))) {
                return new Response(
                    $response->getContent()
                );
            }
            return $this->redirectToRoute($formCampaignCode == 'alwayson_newsletter' ? 'form_newsletter_thanks' : 'form_contact_thanks');
        }

        return $formErrors;
    }

    private function validateForm($data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] =     FormValidation::validationName($data['name']);
        $errorMessages['jobTitleErr'] = FormValidation::validationJobTitle($data['jobTitle']);
        $errorMessages['companyErr'] =  FormValidation::validationCompany($data['company']);
        $errorMessages['emailErr'] =    FormValidation::validationEmail($data['email']);

        if (!($data['callback'] == "No" || $data['callback'] == null)) {
            $errorMessages['phoneErr'] = FormValidation::validationPhone($data['phone']);
        }


        foreach ($errorMessages as $type => $value) {
            if (!empty($errorMessages[$type]['errors'])) {
                return $errorMessages;
            }
        }

        return false;
    }

    private function validateAggregationOptionForm($data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] =              FormValidation::validationName($data['name']);
        $errorMessages['emailErr'] =             FormValidation::validationEmail($data['email']);
        $errorMessages['phoneErr'] =             FormValidation::validationPhone($data['phone']);
        $errorMessages['companyErr'] =           FormValidation::validationCompany($data['company']);
        $errorMessages['jobTitleErr'] =          FormValidation::validationJobTitle($data['jobTitle']);
        $errorMessages['aggregationOptionErr'] = FormValidation::validationAggregationOption($data['aggregationOption']);

        foreach ($errorMessages as $type => $value) {
            if (!empty($errorMessages[$type]['errors'])) {
                return $errorMessages;
            }
        }

        return false;
    }

    private function getFromData($params)
    {
        return [
            'name' => $params->get('name', null),
            'email' => $params->get('email', null),
            'phone' => $params->get('phone', null),
            'company' => $params->get('company', null),
            'jobTitle' => $params->get('jobTitle', null),
            'aggregationOption' =>  $params->get('00Nb0000009IXEW', null),
            'callback' => $params->get('00Nb0000009IXEg', null),
            'callbackTimeslot' => $params->get('callbackTimeslot', null),
            'description' =>  $params->get('description', null),
            'aggregationCheckbox' => $params->get('00Nb0000009IXEd', null),
            'validateAggregationOption' => $params->get('validateAggregationOption', null),
        ];
    }

    /**
     * Simple healthcheck
     *
     * @return JsonResponse
     */
    public function 
    check()
    {
        $required = '8.2.0';
        if (version_compare(PHP_VERSION, $required) < 0) {
            return new JsonResponse(['message' => sprintf("PHP version must be %s or above, found '%s'", $required, PHP_VERSION)], 500);
        }

        // Check Composer has loaded required classes
        $required = [
            \Symfony\Bundle\FrameworkBundle\Controller\AbstractController::class,
            \Strata\Frontend\Cms\RestData::class,
            \Strata\Frontend\Cms\Wordpress::class
        ];
        foreach ($required as $class) {
            if (!class_exists($class)) {
                return new JsonResponse(['message' => sprintf("Class '%s' does not exist", $class)], 500);
            }
        }

        // Check required environment variables
        $required = [
            'APP_API_BASE_URL',
            'APP_ENV'
        ];
        foreach ($required as $variable) {
            if (empty(getenv($variable))) {
                return new JsonResponse(['message' => sprintf("Environment variable '%s' not set", $variable)], 500);
            }
        }

        return new JsonResponse(['message' => 'OK']);
    }

    public function statuscheck()
    {
        $client = HttpClient::create(['verify_peer' => false, 'verify_host' => false, 'timeout' => 5]);
        $listOfUrlToCheck = $this->getHeaderAndFooterListFromCMS($client, getenv('APP_CMS_BASE_URL'));
        $results = [];

        foreach ($listOfUrlToCheck as $url) {
            try {
                $response = $client->request('GET', $url);
                $results[$url] = $response->getstatuscode();
            } catch (TransportExceptionInterface) {
            }
        }
        return $this->render('pages/status_check.html.twig', [
            'results'           => $results,
            'totalLinks'        => count($listOfUrlToCheck)
         ]);
    }


    public function getHeaderAndFooterListFromCMS($client, $APP_CMS_BASE_URL)
    {

        $numbers = ['21','22','23','24','25'];
        $returnList = [];

        foreach ($numbers as $number) {
            $apiUrl = $APP_CMS_BASE_URL . '/wp-json/wp-api-menus/v2/menus/' . $number;
            $CMSresponse = $client->request('GET', $apiUrl);

            if ($CMSresponse->getStatusCode() == 200) {
                $jsonObjects = json_decode((string) $CMSresponse->getContent())->items;

                foreach ($jsonObjects as $jsonObject) {
                    $url = $jsonObject->url;

                    if (filter_var($url, FILTER_VALIDATE_URL)) {
                        $returnList[] = $url;
                    }
                }
            }
        }
        return array_unique($returnList);
    }

    public function digitsLanding()
    {
        // To render digits outage page use
        // $this->render('pages/digits_landing.html.twig');

        return $this->redirect('https://travel.crowncommercial.gov.uk/');
    }

    public function digitsCTM()
    {
        // To render digits outage page use
        // $this->render('pages/digits_ctm.html.twig');

        return $this->redirect('https://travel.crowncommercial.gov.uk/');
    }

    private function extractNewsPropertie($arrayFromEndpoint)
    {

        $arrayOfID = [];

        foreach ($arrayFromEndpoint as $eachID) {
            $arrayOfID[] = $eachID['term_taxonomy_id']->getValue();
        }

        return $arrayOfID;
    }

    public function ppgTraining()
    {

        return $this->render('pages/ppg_training.html.twig');
    }

    public function setCookiesOnSafari(Request $request)
    {
        // Read Current Cookies
        $cookies = $request->cookies;

        // Update cookies with expiry to 1 year
        if ($cookies->has('cookies_reset')) {
            $cookiePreferences = new Cookie('cookie_preferences', '{"essentials":true,"usage":true,"marketing":true, "glassbox": true}', strtotime('+1 year'), '/', '.crowncommercial.gov.uk', false, false);
            $seenCookieMessage = new Cookie('seen_cookie_message', 'true', strtotime('+1 year'), '/', '.crowncommercial.gov.uk', false, false);
            $cookieReset = new Cookie('cookies_reset', 'true', strtotime('+1 year'), '/', '.crowncommercial.gov.uk', false, false);

            $response = new Response();
            $response->headers->setCookie($cookiePreferences);
            $response->headers->setCookie($seenCookieMessage);
            $response->headers->setCookie($cookieReset);
            return $response->sendHeaders();
        }
    }

    public function glossary(Request $request)
    {
        $query = filter_var($request->query->get('termSearch'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        try {
            $results = $this->glossaryApi->getOne(0);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Glossary API broken', $e);
        }

        $meta = $results->getContent()->get('meta')->getValue();

        $results = $results->getContent()->get('glossaries')->getValue();

        $glossaries = [];

        foreach ((array) $results as $glossary) {
            $term = trim((string) $glossary->get('term')->getValue());
            $key = strtoupper($term[0]);

            if (str_contains(strtolower($term), strtolower($query))) {
                $glossaries[$key][] = ['term' => $term, 'meaning' => $glossary->get('meaning')->getValue()];
            }
        }

        ksort($glossaries);

        return $this->render('pages/glossary.html.twig', [
            'glossaries' => $glossaries,
            'termSearch'     => $query,
            'intro_text' => $meta[0]['intro_text']
        ]);
    }
}
