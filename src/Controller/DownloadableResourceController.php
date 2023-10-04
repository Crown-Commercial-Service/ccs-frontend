<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\FormController;
use App\Helper\ControllerHelper;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;
use Strata\Frontend\Cms\Wordpress;
use Strata\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Strata\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DownloadableResourceController extends AbstractController
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

        $this->api = new WordPress(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('downloadable_resources');
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
            $downloadable_resource = $this->api->getPageByUrl('/news/downloadable/' . $sanitisedSlug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Downloadable Resource not found', $e);
        }

        $formErrors = null;
        $params = $request->request;
        $formData = ControllerHelper::getFormData($params);
        $utmParams = $request->query->all();

        $returnURL = getenv('APP_BASE_URL') . '/downloadable-resource/confirmation/' . $downloadable_resource->getId() . '/' . $downloadable_resource->getUrlSlug() . '/?' . filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_STRING);
        $campaignCode = $downloadable_resource->getContent()->get('campaign_code') ? $downloadable_resource->getContent()->get('campaign_code')->getValue() : '';
        $description   = $downloadable_resource->getContent()->get('description') ? $downloadable_resource->getContent()->get('description')->getValue() : '';

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
            'downloadable_resource' => $downloadable_resource,
            'campaign_code' => $campaignCode,
            'form_action'   => $request->getRequestUri(),
            'description'   => $description,
            'return_url'    => $returnURL,
            'formErrors'    => $formErrors,
            'formData'      => $formData,
        ];

        return $this->render('downloadable_resources/request.html.twig', $data);
    }

    public function show($id, $slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $downloadable_resource = $this->api->getPageByUrl('/news/downloadable/' . $slug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Downloadable Resource not found', $e);
        }

        return $this->render('downloadable_resources/confirmation.html.twig', [
            'downloadable_resource' => $downloadable_resource
        ]);
    }
}
