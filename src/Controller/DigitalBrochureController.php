<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\FormController;
use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\Cms\RestData;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\PaginationException;
use Studio24\Frontend\Exception\WordpressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Studio24\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DigitalBrochureController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var Wordpress
     */
    protected $api;

    public function __construct(CacheInterface $cache)
    {

        $this->api = new WordPress(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('digital_brochures');
        $this->api->setCache($cache);
        $this->api->setCacheLifetime(900);
    }

    public function request($id, $slug, Request $request)
    {
        $sanitisedSlug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $digital_brochure = $this->api->getPageByUrl('/news/digital-brochure/' . $sanitisedSlug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Digital Brochure not found', $e);
        }

        $formErrors = null;
        $params = $request->request;
        $formData = $this->getFormData($params);
        $returnURL = getenv('APP_BASE_URL') . '/digital_brochure/confirmation/' . $digital_brochure->getId() . '/' . $digital_brochure->getUrlSlug() . '/?' . filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_STRING);
        $campaignCode = $digital_brochure->getContent()->get('campaign_code') ? $digital_brochure->getContent()->get('campaign_code')->getValue() : '';

        if ($request->isMethod('POST')) {
            $formErrors = FormController::sendToSalesforce($params, $formData, $campaignCode);

            if ($formErrors instanceof Response) {
                return $formErrors;
            }

            if (!$formErrors) {
                return $this->redirect($returnURL);
            }
        }

        $data = [
          'digital_brochure'    => $digital_brochure,
          'campaign_code' => $campaignCode,
          'form_action'   => $request->getRequestUri(),
          'description'   => $digital_brochure->getContent()->get('description') ? $digital_brochure->getContent()->get('description')->getValue() : '',
          'org_id'        => getenv('APP_ENV') === 'prod' ? '00Db0000000egy4' : '00D8E000000E4zz',
          'return_url'    => $returnURL,
          'formErrors'    => $formErrors,
          'formData'      => $formData,
        ];

        return $this->render('digital_brochures/request.html.twig', $data);
    }

    public function show($id, $slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_STRING);

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
