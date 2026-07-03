<?php

declare(strict_types=1);

namespace App\Controller;

use App\Validation\FormValidation;
use App\Helper\ControllerHelper;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;
use Strata\Frontend\Cms\Wordpress;
use Strata\Frontend\Cms\RestData;
use Strata\Frontend\ContentModel\ContentModel;
use Strata\Frontend\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends AbstractController
{
    protected Wordpress $api;
    protected RestData $redirectionApi;
    protected RestData $glossaryApi;
    protected HttpClientInterface $client;
    protected ControllerHelper $controllerHelper;

    protected string $appApiBaseUrl;
    protected string $appBaseUrl;
    protected string $appCmsBaseUrl;
    protected string $salesforceWebToCaseUrl;
    protected string $appEnv;

    public function __construct(
        CacheItemPoolInterface $cache,
        Wordpress $api,
        HttpClientInterface $httpClient,
        RestData $redirectionApi,
        ControllerHelper $controllerHelper,
        string $appApiBaseUrl,
        string $appBaseUrl,
        string $appCmsBaseUrl,
        string $salesforceWebToCaseUrl,
        string $appEnv // Explicitly inject the environment name for healthchecks
    ) {
        $this->api = $api;
        $this->controllerHelper = $controllerHelper;

        $psr16Cache = new Psr16Cache($cache);
        $this->api->setContentType('page');
        $this->api->setCache($psr16Cache);
        $this->api->setCacheLifetime(900);

        $this->client = $httpClient;

        $this->appApiBaseUrl = $appApiBaseUrl;
        $this->appBaseUrl = $appBaseUrl;
        $this->appCmsBaseUrl = $appCmsBaseUrl;
        $this->salesforceWebToCaseUrl = $salesforceWebToCaseUrl;
        $this->appEnv = $appEnv;

        $contentModel = new ContentModel(__DIR__ . '/../../config/content/content-model.yaml');

        $this->redirectionApi = $redirectionApi;
        $this->redirectionApi->setContentType('redirections');

        $this->glossaryApi = new RestData($this->appApiBaseUrl, $contentModel);
        $this->glossaryApi->setContentType('glossary');
    }

    public function home(Request $request)
    {
        $this->api->setCacheKey($request->getRequestUri());
        $flag = filter_var($request->query->get('feature'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $this->api->setContentType('news');
        $news = $this->api->listPages(1, ['per_page' => 3]);

        $homepageCompUrl = $this->appApiBaseUrl . 'ccs/v1/homepage-components/0';


        $messageBanner = $this->controllerHelper->getHomeMessageBanner();


        $response = $this->client->request('GET', $homepageCompUrl);
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

    public function page(string $slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $redirectedLink = $this->checkRedirect($slug);

        if ($redirectedLink != '') {
            return $this->redirect($redirectedLink);
        }

        try {
            $this->api->setCacheKey($request->getRequestUri());
            $page = $this->api->getPageByUrl($request->getRequestUri());
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Page not found', $e);
        }

        $parts = explode('/', trim($slug, '/'));
        array_pop($parts);
        $breadcrumb = [];
        $link = '';
        foreach ($parts as $part) {
            $name = ucfirst(str_replace('-', ' ', $part));
            $link .= '/' . $part;
            $breadcrumb[$link] = $name;
        }

        $optionCardsUrl = $this->appApiBaseUrl . 'ccs/v1/option-cards/0';
        $response = $this->client->request('GET', $optionCardsUrl);
        $optionCardsContent = null;

        if ($response->getStatusCode() == 200) {
            $optionCardsContent = json_decode((string) $response->getContent());
        }

        $formErrors = null;
        $formData = $this->getFromData($request->request);
        $formCampaignCode = null;

        if (!$page) {
            throw $this->createNotFoundException('Page not found');
        }

        if ($page->getContent()->get('contact_form_form_campaign_code') !== null) {
            $formCampaignCode = $page->getContent()['contact_form_form_campaign_code']->getValue();
        }

        if ($request->isMethod('POST')) {
            $formErrors = $this->sendToSalesforceForPageEnquiry($request->request, $formData, $formCampaignCode);
            if ($formErrors instanceof Response) {
                return $formErrors;
            }
        }

        $cscMessage = $this->controllerHelper->getCSCMessage();
        $resourcesWithIndex = $this->extractResourcesFromContent($page->getContent());

        return $this->render('pages/page.html.twig', [
            'page'                       => $page,
            'breadcrumb_parents'         => $breadcrumb,
            'page_query_string'          => filter_var($request->server->get('QUERY_STRING', ''), FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            'query_string_type'          => $request->query->get('type') ? filter_var($request->query->get('type'), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : null,
            'site_base_url'              => $this->appBaseUrl,
            'option_cards'               => $optionCardsContent,
            'slug'                       => $slug,
            'formErrors'                 => $formErrors,
            'formData'                   => $formData,
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
            $results = $this->redirectionApi->getOne(0);
        } catch (\Throwable $e) {
            return '';
        }

        try {
            $listOfRedirection = $results->getContent()->get('results')->getValue();
            foreach ($listOfRedirection as $redirection) {
                $shortenUrl = $redirection->get('shortUrl')->getValue();
                $longUrl = $this->appBaseUrl . "/" . $redirection->get('longUrl')->getValue();

                if ($shortenUrl == $slug) {
                    return $longUrl;
                }
            }
        } catch (\Throwable $e) {
            return '';
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
            $params->set('orgid', $this->controllerHelper->getOrgId());

            $origin = $params->get('newsletterForm') ? 'Website - Newsletter' : 'Website - Page form enquiry';

            $params->set('origin', $origin);
            $params->set('description', $origin . ', callback: ' . $formData['callbackTimeslot'] . ', more-detail: ' . $formData['description']);

            $response = $this->client->request('POST', $this->salesforceWebToCaseUrl, [
                'query' => $params->all(),
            ]);

            if (!is_null($params->get('debug'))) {
                return new Response(htmlspecialchars($response->getContent()));
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

    public function check()
    {
        $required = '8.2.0';
        if (version_compare(PHP_VERSION, $required) < 0) {
            return new JsonResponse(['message' => sprintf("PHP version must be %s or above, found '%s'", $required, PHP_VERSION)], 500);
        }

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

        if (empty($this->appApiBaseUrl) || empty($this->appEnv)) {
            return new JsonResponse(['message' => "Required Configuration properties are not properly injected"], 500);
        }

        return new JsonResponse(['message' => 'OK']);
    }

    public function sitemap()
    {
        $response = $this->client->request('GET', $this->appCmsBaseUrl . '/wp-json/ccs/v1/sitemap');

        if ($response->getStatusCode() !== 200) {
            throw $this->createNotFoundException('Sitemap not available.');
        }

        $data = $response->toArray();

        return new Response(
            $data['raw_xml'],
            Response::HTTP_OK,
            ['Content-Type' => 'text/xml']
        );
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

    public function ppgTraining()
    {
        return $this->render('pages/ppg_training.html.twig');
    }

    public function setCookiesOnSafari(Request $request)
    {
        $cookies = $request->cookies;

        if ($cookies->has('cookies_reset')) {
            $cookiePreferences = new Cookie('cookie_preferences', '{"essentials":true,"usage":true,"marketing":true, "glassbox": true}', strtotime('+1 year'), '/', '.gca.gov.uk', false, false);
            $seenCookieMessage = new Cookie('seen_cookie_message', 'true', strtotime('+1 year'), '/', '.gca.gov.uk', false, false);
            $cookieReset = new Cookie('cookies_reset', 'true', strtotime('+1 year'), '/', '.gca.gov.uk', false, false);

            $response = new Response();
            $response->headers->setCookie($cookiePreferences);
            $response->headers->setCookie($seenCookieMessage);
            $response->headers->setCookie($cookieReset);
            return $response->sendHeaders();
        }
    }

    public function glossary(Request $request)
    {
        $query = $request->query->get('termSearch') ?? '';

        try {
            $results = $this->glossaryApi->getOne(0);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Glossary API broken', $e);
        }

        $meta = $results->getContent()->get('meta')->getValue();
        $results = $results->getContent()->get('glossaries')->getValue();

        $glossaries = [];

        foreach ((array) $results as $glossary) {
            $keywords = [];
            $term = trim((string) $glossary->get('term')->getValue());
            $key = strtoupper($term[0]);

            foreach ($glossary->get('keyword') ?? [] as $keyword) {
                $keywords[] = $keyword->get('searchable_keyword')->getValue();
            }

            if (str_contains(strtolower($term), strtolower($query)) || in_array(strtolower($query), array_map('strtolower', $keywords))) {
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
