<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\FormController;
use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\PaginationException;
use Studio24\Frontend\Exception\WordpressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Studio24\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WebinarController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var Wordpress
     */
    protected $api;

    public function __construct(CacheInterface $cache)
    {
        $this->api = new Wordpress(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('webinars');
        $this->api->setCache($cache);
        $this->api->setCacheLifetime(900);
    }

    public function request($id, $slug, Request $request)
    {
        $sanitisedSlug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $webinar = $this->api->getPageByUrl('/news/webinars/' . $sanitisedSlug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Webinar not found', $e);
        }

        $formErrors = null;
        $params = $request->request;
        $formData = $this->getFormData($params);
        $returnURL = getenv('APP_BASE_URL') . '/webinar/confirmation/' . $webinar->getId() . '/' . $webinar->getUrlSlug() . '/?' . filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_STRING);

        if ($request->isMethod('POST')) {
            $formErrors = FormController::gatedFormErrors($formData);

            if ($formErrors instanceof Response) {
                return $formErrors;
            }

            if (!$formErrors) {
                // create client
                $client = HttpClient::create();
                $campaignCode = $webinar->getContent()->get('campaign_code') ? $webinar->getContent()->get('campaign_code')->getValue() : '';

                $params->set('subject', $campaignCode);
                $params->set('00Nb0000009IXEW', $campaignCode);

                $response = $client->request('POST', getenv('SALESFORCE_WEB_TO_CASE_URL'), [
                    // these values are automatically encoded before including them in the URL
                    'query' => $params->all(),
                ]);
                return $this->redirect($returnURL);
               }
        }

        $data = [
          'webinar'       => $webinar,
          'campaign_code' => $campaignCode,
          'form_action'   => $request->getRequestUri(),
          'description'   => $webinar->getContent()->get('description') ? $webinar->getContent()->get('description')->getValue() : '',
          'return_url'    => $returnURL,
          'org_id'        => getenv('APP_ENV') === 'prod' ? '00Db0000000egy4' : '00D8E000000E4zz',
          'formErrors'    => $formErrors,
          'formData'      => $formData,
        ];
        return $this->render('webinars/request.html.twig', $data);
    }

    public function show($slug, Request $request)
    {
        $sanitisedSlug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $webinar = $this->api->getPageByUrl('/news/webinars/' . $sanitisedSlug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('News page not found', $e);
        }

        $data = [
            'webinar' => $webinar
        ];
        return $this->render('webinars/confirmation.html.twig', $data);
    }

    public function getFormData ($params) {
        return [
            'name' => $params->get('name', null),
            'email' => $params->get('email', null),
            'phone' => $params->get('phone', null),
            'company' => $params->get('company', null),
        ];
    }
}
