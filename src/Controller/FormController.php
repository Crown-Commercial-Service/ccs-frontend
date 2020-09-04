<?php

declare(strict_types=1);

namespace App\Controller;

use Studio24\Frontend\Cms\RestData;
use Studio24\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Psr\SimpleCache\CacheInterface;

/**
 * Form controller
 */
class FormController extends AbstractController
{

    /**
     * Frameworks Rest API data
     *
     * @var RestData
     */
    protected $api;

    protected $client;

    public function __construct(CacheInterface $cache)
    {
        $this->api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('frameworks');
        $this->api->setCache($cache);
        $this->client = HttpClient::create();
    }

    /**
     * eSourcingTraining Form template
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentFieldNotSetException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function eSourcingTraining(Request $request)
    {
        $this->api->setContentType('esourcing_dates');
        $this->api->setCacheKey($request->getRequestUri());

        // @todo At present need to pass fake ID since API method is intended to return one item with an ID, review this
        $results = $this->api->getOne(0);

        $data = ['esourcingDates' => $results];

        return $this->render('forms/31-esourcing-training.html.twig', $data);
    }

    public function thankYou(Request $request)
    {
        $data = [];

        return $this->render('forms/thank-you.html.twig', $data);
    }

    public function sendToSalesforce(Request $request)
    {

        $params = $request->request;
        
        $response = $this->client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
            // these values are automatically encoded before including them in the URL
            'query' => $params->all(),
        ]);

        if (!is_null($params->get('debug'))) {
            return new Response(
                $response->getContent()
            );
        } elseif ($params->get('origin') == 'Website - Enquiry') {
            return $this->redirectToRoute('form_contact_thanks');
        } else {
            return $this->redirectToRoute('form_contact_thanks_complaint');
        }
    }
}
