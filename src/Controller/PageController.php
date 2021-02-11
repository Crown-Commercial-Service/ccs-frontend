<?php

declare(strict_types=1);

namespace App\Controller;

use App\Validation\FormValidation;
use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\Cms\RestData;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\FailedRequestException;
use Studio24\Frontend\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    public function __construct(CacheInterface $cache)
    {
        $this->api = new Wordpress(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('page');
        $this->api->setCache($cache);
        $this->api->setCacheLifetime(900);
        $this->client = HttpClient::create();

        $this->redirectionApi = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->redirectionApi->setContentType('redirections');
    }

    /**
     * Homepage
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function home(Request $request)
    {
        $this->api->setCacheKey($request->getRequestUri());
        $flag = filter_var($request->query->get('feature'), FILTER_SANITIZE_STRING);

        $this->api->setContentType('news');
        $news = $this->api->listPages(1, ['limit' => 3]);

        // request to homepage components
        $homepageCompUrl = getenv('APP_API_BASE_URL') . 'ccs/v1/homepage-components/0';

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

        ]);
    }

    /**
     * Generic page controller
     *
     * @param string $slug
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ApiException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function page(string $slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_STRING);
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
            $optionCardsContent = json_decode($response->getContent());
        }

        $formErrors = null;
        $formData = $this->getFromData($request->request);

        if ($request->isMethod('POST')) {
            $formErrors = $this->sendToSalesforce($request->request, $formData);

            if ($formErrors instanceof Response) {
                return $formErrors;
            }
        }

        return $this->render('pages/page.html.twig', [
            'page'               => $page,
            'breadcrumb_parents' => $breadcrumb,
            'page_query_string'  => filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_STRING),
            'query_string_type'  => isset($_GET['type']) ? filter_var($_GET['type'], FILTER_SANITIZE_STRING) : null,
            'site_base_url'      => getenv('APP_BASE_URL'),
            'org_id' => getenv('APP_ENV') === 'prod' ? '00Db0000000egy4' : '00D8E000000E4zz',
            'option_cards' => $optionCardsContent,
            'slug'               => $slug,
            'formErrors'         => $formErrors,
            'formData'           => $formData,
         ]);
    }

    private function checkRedirect($slug)
    {
        $slug = strtolower($slug);

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

    private function sendToSalesforce($params, $formData)
    {

        if (strpos($params->get('retURL'), 'thank-you-page-newsletters')) {
            $formErrors = $this->validateNewsletterForm($formData);
        } else {
            $formErrors = $this->validateForm($formData);
        }

        if ($formErrors) {
            return $formErrors;
        } else {
            $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                            // these values are automatically encoded before including them in the URL
                            'query' => $params->all(),
                        ]);

            if (!is_null($params->get('debug'))) {
                return new Response(
                    $response->getContent()
                );
            }

            return $this->redirect($params->get('retURL'));
        }
    }

    private function validateNewsletterForm($data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] = FormValidation::validationName($data['name']);
        $errorMessages['emailErr'] = FormValidation::validationEmail($data['email']);
        $errorMessages['companyErr'] = FormValidation::validationCompany($data['company']);

        foreach ($errorMessages as $type => $value) {
            if (!empty($errorMessages[$type]['errors'])) {
                return $errorMessages;
            }
        }

        return false;
    }

    private function validateForm($data)
    {
        $errorMessages = [];

        $errorMessages['nameErr'] = FormValidation::validationName($data['name']);
        $errorMessages['emailErr'] = FormValidation::validationEmail($data['email']);
        $errorMessages['phoneErr'] = FormValidation::validationPhone($data['phone']);
        $errorMessages['companyErr'] = FormValidation::validationCompany($data['company']);
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
            'aggregationOption' =>  $params->get('00Nb0000009IXEW', null),
            'callback' => $params->get('00Nb0000009IXEg', null),
            'description' =>  $params->get('description', null),
            'aggregationCheckbox' => $params->get('00Nb0000009IXEd', null),
            'returnLink' => $params->get('retURL', null),
        ];
    }


    /**
     * Simple healthcheck
     *
     * @return JsonResponse
     */
    public function healthcheck()
    {
        $required = '7.1.3';
        if (version_compare(PHP_VERSION, $required) < 0) {
            return new JsonResponse(['message' => sprintf("PHP version must be %s or above, found '%s'", $required, PHP_VERSION)], 500);
        }

        // Check Composer has loaded required classes
        $required = [
            'Symfony\Bundle\FrameworkBundle\Controller\AbstractController',
            'Studio24\Frontend\Cms\RestData',
            'Studio24\Frontend\Cms\Wordpress'
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
            } catch (TransportExceptionInterface $e) {
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
                $jsonObjects = json_decode($CMSresponse->getContent())->items;

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
}
