<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\PardotException;
use App\Utils\Pardot;
use Psr\SimpleCache\CacheInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use App\Exception\ApiException;

class ApiController extends AbstractController
{

    /**
     * Return CMS API URL
     *
     * @param string $path Optionally add path to API URL
     * @return string
     * @throws ApiException
     */
    public function getCmsUrl(string $path = ''): string
    {
        $url = $_ENV['APP_CMS_BASE_URL'];
        if (empty($url)) {
            throw new ApiException('Cannot determine CMS API URL');
        }

        $url = rtrim($url, '/');
        return $url . $path;
    }

    /**
     * API proxy for Suppliers search
     *
     * Proxy requests from https://FRONTEND/api/suppliers?keyword=l&framework=&lot=&limit=20&page=1
     * to: https://CMS/search-api/suppliers?keyword=l&framework=&lot=&limit=20&page=1
     *
     * @param Request $request
     * @param CacheInterface $cache
     * @return JsonResponse
     * @throws ApiException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function suppliers(Request $request, CacheInterface $cache)
    {
        $apiUrl = $this->getCmsUrl('/search-api/suppliers');

        // Build query and input filter params
        $client = HttpClient::create();
        $response = $client->request(
            'GET',
            $apiUrl,
            [
                'query' => $this->filterSupplierParams($request->query->all())
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new ApiException(sprintf('Error with Search Suppliers API query, API status code: %s, API status message: %s', $response->getStatusCode(), $response->getContent()));
        }

        $responseFinal = json_decode($response->getContent());
        return new JsonResponse($responseFinal);
    }

    /**
     * Return a filtered array of search params for supplier API query
     *
     * Please note any GET params you want to allow to be passed onto the WP API must be added here
     *
     * @param array $params
     * @return array
     */
    private function filterSupplierParams(array $params)
    {
        $filtered = [];
        foreach ($params as $name => $param) {
            switch ($name) {
                case 'keyword':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_STRING);
                    break;
                case 'framework':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_STRING);
                    break;
                case 'lot':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_NUMBER_INT);
                    break;
                case 'limit':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_NUMBER_INT);
                    break;
                case 'page':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_NUMBER_INT);
                    break;
            }
        }
        return $filtered;
    }

    /**
     * API proxy for Frameworks search
     *
     * Proxy requests from https://FRONTEND/api/frameworks?keyword=l&limit=20&page=1
     * to: https://CMS/search-api/suppliers?keyword=l&limit=20&page=1
     *
     * @param Request $request
     * @param CacheInterface $cache
     * @return JsonResponse
     * @throws ApiException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function frameworks(Request $request, CacheInterface $cache)
    {
        $apiUrl = $this->getCmsUrl('/search-api/frameworks');

        // Build query and input filter params
        $client = HttpClient::create();
        $response = $client->request(
            'GET',
            $apiUrl,
            [
                'query' => $this->filterFrameworkParams($request->query->all())
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new ApiException(sprintf('Error with Search Framework API query, API status code: %s, API status message: %s', $response->getStatusCode(), $response->getMessage()));
        }

        $responseFinal = json_decode($response->getContent());
        return new JsonResponse($responseFinal);
    }

    /**
     * Return a filtered array of search params for framework API query
     *
     * Please note any GET params you want to allow to be passed onto the WP API must be added here
     *
     * @param array $params
     * @return array
     */
    private function filterFrameworkParams(array $params)
    {
        $filtered = [];
        foreach ($params as $name => $param) {
            switch ($name) {
                case 'keyword':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_STRING);
                    break;
                case 'status':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_STRING);
                    break;
                case 'limit':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_NUMBER_INT);
                    break;
                case 'page':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_NUMBER_INT);
                    break;
                case 'pillar':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_STRING);
                    break;
                case 'category':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_STRING);
                    break;
            }
        }
        return $filtered;
    }

    /**
     * Send email address to Pardot
     *
     * This sends submitted email addresses to a Pardot form to help with marketing tracking
     *
     * We're only expecting AJAX POST requests to this controller action with a POST variable 'email'
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function pardotEmail(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            return new JsonResponse(['message' => 'You can only send AJAX (XMLHttpRequest) requests to this URL'], 400);
        }

        $data = json_decode($request->getContent());

        if (is_null($data) || !isset($data->email)) {
            return new JsonResponse(['message' => 'You must pass an email variable with this request'], 400);
        }

        $email = $data->email;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['message' => 'An invalid email has been passed'], 400);
        }

        $pardotFormUrl = getenv('PARDOT_EMAIL_FORM_HANDLER_URL');
        if (empty($pardotFormUrl) || !filter_var($pardotFormUrl, FILTER_VALIDATE_URL)) {
            return new JsonResponse(['message' => 'Please set PARDOT_EMAIL_FORM_HANDLER_URL or ensure this is a valid URL'], 400);
        }

        // Build extra data to send to Pardot
        $extraData = [];
        foreach ($data as $key => $val) {
            if ($key == 'email') {
                continue;
            }
            $extraData[$key] = $val;
        }

        // Send Pardot form handler request
        $pardot = new Pardot();
        if ($pardot->submitEmail($pardotFormUrl, $email, $extraData)) {
            return new JsonResponse(['message' => 'OK']);
        } else {
            $response = $pardot->getLastResponse();
            throw new PardotException(sprintf('Error sending email data to Pardot. HTTP status code: %s, Message: %s', $response->getStatusCode(), $response->getContent()));
        }
    }
}
