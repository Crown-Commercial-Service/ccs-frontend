<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\ControllerHelper;
use Strata\Frontend\Cms\Wordpress;
use Strata\Frontend\ContentModel\ContentModel;
use Strata\Frontend\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Psr\Cache\CacheItemPoolInterface;

class DigitalBrochureController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var Wordpress
     */
    protected $api;
    protected $formController;
    protected string $appBaseUrl;

    public function __construct(
        CacheItemPoolInterface $cache,
        FormController $formController,
        Wordpress $api,
        string $appBaseUrl
    ) {
        $this->api = $api;
        $this->api->setContentType('digital_brochures');

        $psr16Cache = new Psr16Cache($cache);
        $this->api->setCache($psr16Cache);
        $this->api->setCacheLifetime(900);

        $this->formController = $formController;
        $this->appBaseUrl = $appBaseUrl;
    }

    public function request($id, $slug, Request $request)
    {
        $sanitisedSlug = filter_var($slug, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        switch ($sanitisedSlug) {
            case "commercial-agreements-spring-2021-digital-brochure":
            case "commercial-agreements-autumn-2021-digital-brochure":
                return $this->redirectToRoute('digital_brochure_request', ['id' => '111', 'slug' => 'commercial-agreements-digital-brochure']);
                break;
            case "digital-transformation-guide-technology-procurement-for-local-government":
                return $this->redirectToRoute('downloadable_resource_request', ['id' => '111', 'slug' => 'digital-transformation-guide-technology-procurement-for-local-government']);
                break;
        }

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $digital_brochure = $this->api->getPageByUrl('/news/digital-brochure/' . $sanitisedSlug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Digital Brochure not found', $e);
        }

        $formErrors = null;
        $params = $request->request;
        $formData = ControllerHelper::getFormData($params);
        $utmParams = $request->query->all();

        $queryString = $request->getQueryString() ?? '';
        $returnURL = $this->appBaseUrl . '/digital_brochure/confirmation/' . $digital_brochure->getId() . '/' . $digital_brochure->getUrlSlug() . '/?' . filter_var($queryString, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $campaignCode = $digital_brochure->getContent()->get('campaign_code') ? $digital_brochure->getContent()->get('campaign_code')->getValue() : '';
        $description   = $digital_brochure->getContent()->get('description') ? $digital_brochure->getContent()->get('description')->getValue() : '';

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
          'digital_brochure'    => $digital_brochure,
          'campaign_code' => preg_replace('/\s*/', '', (string) $campaignCode),
          'form_action'   => $request->getRequestUri(),
          'description'   => $description,
          'return_url'    => $returnURL,
          'formErrors'    => $formErrors,
          'formData'      => $formData,
        ];

        return $this->render('digital_brochures/request.html.twig', $data);
    }

    public function show($id, $slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $digital_brochure = $this->api->getPageByUrl('/news/digital-brochure/' . $slug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Digital Brochure not found', $e);
        }

        return $this->render('digital_brochures/confirmation.html.twig', [
            'digital_brochure' => $digital_brochure
        ]);
    }

    public function redirectToDownloadableResource($id, $slug, Request $request)
    {
         return $this->redirect($this->generateUrl('downloadable_resource_request', ['id' => $id, 'slug' => $slug]));
    }
}
