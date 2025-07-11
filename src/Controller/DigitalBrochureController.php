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

class DigitalBrochureController extends AbstractController
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
        $this->api->setContentType('digital_brochures');
        $psr16Cache = new Psr16Cache($cache);
        $this->api->setCache($psr16Cache);
        $this->api->setCacheLifetime(900);
        $this->formController = new FormController($cache);
    }

    public function request($id, $slug, Request $request)
    {
        $sanitisedSlug = filter_var($slug, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        switch ($sanitisedSlug) {
            case "commercial-agreements-spring-2021-digital-brochure":
            case "commercial-agreements-autumn-2021-digital-brochure":
                // id is just a dummy value
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

        $returnURL = getenv('APP_BASE_URL') . '/digital_brochure/confirmation/' . $digital_brochure->getId() . '/' . $digital_brochure->getUrlSlug() . '/?' . filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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
