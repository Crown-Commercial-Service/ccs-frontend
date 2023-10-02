<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\FormController;
use App\Helper\ControllerHelper;
use Symfony\Component\Cache\Psr16Cache;
use Psr\Cache\CacheItemPoolInterface;
use Strata\Frontend\Cms\Wordpress;
use Strata\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Strata\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WebinarController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var Wordpress
     */
    protected $api;
    protected $formController;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->api = new Wordpress(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('webinars');
        $psr16Cache = new Psr16Cache($cache);
        $this->api->setCache($psr16Cache);
        $this->api->setCacheLifetime(900);
        $this->formController = new FormController($cache);
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
        $formData = ControllerHelper::getFormData($params);
        $utmParams = $request->query->all();

        $returnURL = getenv('APP_BASE_URL') . '/webinar/confirmation/' . $webinar->getId() . '/' . $webinar->getUrlSlug() . '/?' . filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_STRING);
        $campaignCode = $webinar->getContent()->get('campaign_code') ? $webinar->getContent()->get('campaign_code')->getValue() : '';
        $description   = $webinar->getContent()->get('description') ? $webinar->getContent()->get('description')->getValue() : '';


        if ($request->isMethod('POST')) {
            ControllerHelper::honeyPot($params->get('surname', null));

            $formErrors = $this->formController->sendToSalesforceForDownload($params, $utmParams, $formData, $campaignCode, $description);

            if ($formErrors instanceof Response) {
                return $formErrors;
            }

            if (!$formErrors) {
                return $this->redirect($returnURL);
            }
        }

        $data = [
          'webinar'       => $webinar,
          'campaign_code' => $campaignCode,
          'form_action'   => $request->getRequestUri(),
          'description'   => $description,
          'return_url'    => $returnURL,
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
}
