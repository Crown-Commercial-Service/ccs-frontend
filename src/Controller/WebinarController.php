<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\FormController;
use App\Helper\ControllerHelper;
use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        $campaignCode = $webinar->getContent()->get('campaign_code') ? $webinar->getContent()->get('campaign_code')->getValue() : '';
        $description   = $webinar->getContent()->get('description') ? $webinar->getContent()->get('description')->getValue() : '';


        if ($request->isMethod('POST')) {
            $formErrors = FormController::sendToSalesforce($params, $formData, $campaignCode, $description);

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

    public function getFormData($params)
    {
        return [
            'name' => $params->get('name', null),
            'email' => $params->get('email', null),
            'phone' => $params->get('phone', null),
            'company' => $params->get('company', null),
        ];
    }
}
