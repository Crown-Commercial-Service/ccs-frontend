<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\PardotException;
use App\Utils\Pardot;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Exception\ApiException;

class ApiController extends AbstractController
{
    protected HttpClientInterface $client;
    protected string $appCmsBaseUrl;
    protected array $pardotUrls;

    public function __construct(
        HttpClientInterface $client,
        string $appCmsBaseUrl,
        array $pardotUrls
    ) {
        $this->client = $client;
        $this->appCmsBaseUrl = $appCmsBaseUrl;
        $this->pardotUrls = $pardotUrls;
    }

    /**
     * Return CMS API URL
     *
     * @param string $path Optionally add path to API URL
     * @return string
     * @throws ApiException
     */
    public function getCmsUrl(string $path = ''): string
    {
        $url = $this->appCmsBaseUrl;
        if (empty($url)) {
            throw new ApiException('Cannot determine CMS API URL');
        }

        $url = rtrim((string) $url, '/');
        return $url . $path;
    }

    private function filterParams(array $params, array $allowedFilters)
    {
        $filtered = [];
        foreach ($params as $name => $param) {
            if (array_key_exists($name, (array)$allowedFilters)) {
                $filtered[$name] = filter_var($param, $allowedFilters[$name]);
            }
        }

        return $filtered;
    }

    private function getResponse($apiUrl, $request, $allowedFilters)
    {
        return $this->client->request('GET', $apiUrl, ['query' => $this->filterParams($request->query->all(), $allowedFilters)]);
    }

    /**
     * API proxy for Suppliers search
     */
    public function suppliers(Request $request, CacheItemPoolInterface $cache)
    {
        $apiUrl = $this->getCmsUrl('/search-api/suppliers');

        $allowedFilters = [
            'keyword'   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'framework' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'lot'       => FILTER_SANITIZE_NUMBER_INT,
            'limit'     => FILTER_SANITIZE_NUMBER_INT,
            'page'      => FILTER_SANITIZE_NUMBER_INT,
        ];

        $response = $this->getResponse($apiUrl, $request, $allowedFilters);

        if ($response->getStatusCode() !== 200) {
            throw new ApiException(sprintf('Error with Search Suppliers API query, API status code: %s, API status message: %s', $response->getStatusCode(), $response->getContent()));
        }

        $responseFinal = json_decode((string) $response->getContent());
        return new JsonResponse($responseFinal);
    }

    /**
     * API proxy for Frameworks search
     */
    public function frameworks(Request $request, CacheItemPoolInterface $cache)
    {
        $apiUrl = $this->getCmsUrl('/search-api/frameworks');

        // ✅ FIX: Use injected HTTP client
        $response = $this->client->request(
            'GET',
            $apiUrl,
            [
                'query' => $this->filterFrameworkParams($request->query->all())
            ]
        );

        if ($response->getStatusCode() !== 200) {
            throw new ApiException(sprintf('Error with Search Framework API query, API status code: %s, API status message: %s', $response->getStatusCode(), $response->getContent()));
        }

        $responseFinal = json_decode($response->getContent());
        return new JsonResponse($responseFinal);
    }

    /**
     * Return a filtered array of search params for framework API query
     */
    private function filterFrameworkParams(array $params)
    {
        $filtered = [];
        foreach ($params as $name => $param) {
            switch ($name) {
                case 'status':
                case 'pillar':
                case 'category':
                case 'regulation':
                case 'regulation_type':
                case 'terms':
                    if (!is_array($param)) {
                        $filtered[$name] = filter_var($param, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    } else {
                        $filtered[$name] = filter_var_array($param);
                    }
                    break;
                case 'keyword':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                    break;
                case 'sort':
                    $filtered[$name] = filter_var($param, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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
     * API proxy for news filter
     */
    public function news(Request $request, CacheItemPoolInterface $cache)
    {
        $apiUrl = $this->getCmsUrl('/wp-json/ccs/v1/news');

        $allowedFilters = [
            'categories'        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'whitepaper'        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'webinar'           => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'digitalDownload'   => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'noPost'            => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'sectors'           => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'products_services' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
            'page'              => FILTER_SANITIZE_NUMBER_INT,
            'per_page'          => FILTER_SANITIZE_NUMBER_INT,
        ];

        $response = $this->getResponse($apiUrl, $request, $allowedFilters);

        if ($response->getStatusCode() !== 200) {
            throw new ApiException(sprintf('Error with news filter API query, API status code: %s, API status message: %s', $response->getStatusCode(), $response->getContent()));
        }
        $responseFinal['meta']['X-WP-TotalPages'] = (int) $response->getHeaders()["x-wp-totalpages"][0];
        $responseFinal['meta']['X-WP-Total'] = (int) $response->getHeaders()["x-wp-total"][0];
        $responseFinal['body'] = json_decode((string) $response->getContent());
        return new JsonResponse($responseFinal);
    }

    /**
     * API proxy for events filter
     */
    public function events(Request $request, CacheItemPoolInterface $cache)
    {
        $apiUrl = $this->getCmsUrl('/wp-json/wp/v2/event');

        $allowedFilters = [
            'sectors'            => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
             'products_services' => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
             'event_type'        => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
             'audience_tag'      => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
             'page'              => FILTER_SANITIZE_NUMBER_INT,
             'per_page'          => FILTER_SANITIZE_NUMBER_INT,
             'orderby'           => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
             'order'             => FILTER_SANITIZE_FULL_SPECIAL_CHARS,
        ];

        $response = $this->getResponse($apiUrl, $request, $allowedFilters);

        if ($response->getStatusCode() !== 200) {
            throw new ApiException(sprintf('Error with event filter API query, API status code: %s, API status message: %s', $response->getStatusCode(), $response->getContent()));
        }
        $responseFinal['meta']['X-WP-TotalPages'] = (int) $response->getHeaders()["x-wp-totalpages"][0];
        $responseFinal['meta']['X-WP-Total'] = (int) $response->getHeaders()["x-wp-total"][0];
        $responseFinal['body'] = json_decode((string) $response->getContent());
        return new JsonResponse($responseFinal);
    }

    /**
     * Send email address to Pardot
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

        if (is_null($data) || !isset($data->subject)) {
            return new JsonResponse(['message' => 'You must pass a subject variable with this request'], 400);
        }

        $pardotFormUrl = $this->setPardotFormURL($data->subject);

        if (empty($pardotFormUrl) || !filter_var($pardotFormUrl, FILTER_VALIDATE_URL)) {
            return new JsonResponse(['message' => 'Please set PARDOT_EMAIL_FORM_HANDLER_URL or ensure this is a valid URL'], 400);
        }

        // Build extra data to send to Pardot
        $extraData = [];
        foreach ($data as $key => $val) {
            if ($key == 'email' || $key == 'subject') {
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

    /**
     * sets pardot form URL based on campaign code (subject)
     */
    public function setPardotFormURL(string $subject)
    {
        $subject = preg_replace('/\s*/', '', $subject);
        $subject = strtolower((string) $subject);

        $pardotFormUrl = null;

        foreach ($this->pardotUrls as $code => $url) {
            if (str_contains($subject, $code)) {
                $pardotFormUrl = $url;
                break;
            }
        }

        return $pardotFormUrl;
    }
}