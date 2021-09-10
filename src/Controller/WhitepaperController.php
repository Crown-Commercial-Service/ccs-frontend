<?php

declare(strict_types=1);

namespace App\Controller;

use App\Controller\FormController;
use App\Helper\ControllerHelper;
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

class WhitepaperController extends AbstractController
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
        $this->api->setContentType('whitepapers');
        $this->api->setCache($cache);
        $this->api->setCacheLifetime(900);
    }

    public function request($id, $slug, Request $request)
    {
        $sanitisedSlug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $whitepaper = $this->api->getPageByUrl('/news/whitepapers/' . $sanitisedSlug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Whitepaper not found', $e);
        }

        $formErrors = null;
        $params = $request->request;
        $formData = $this->getFormData($params);
        $returnURL = getenv('APP_BASE_URL') . '/whitepaper/confirmation/' . $whitepaper->getId() . '/' . $whitepaper->getUrlSlug() . '/?' . filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_STRING);
        $campaignCode = $whitepaper->getContent()->get('campaign_code') ? $whitepaper->getContent()->get('campaign_code')->getValue() : '';

        if ($request->isMethod('POST')) {
            $params->set('orgid', ControllerHelper::getOrgId());
            $formErrors = FormController::sendToSalesforce($params, $formData, $campaignCode);

            if ($formErrors instanceof Response) {
                return $formErrors;
            }

            if (!$formErrors) {
                return $this->redirect($returnURL);
            }
        }

        $data = [
          'whitepaper'    => $whitepaper,
          'campaign_code' => $campaignCode,
          'form_action'   => $request->getRequestUri(),
          'description'   => $whitepaper->getContent()->get('description') ? $whitepaper->getContent()->get('description')->getValue() : '',
          'return_url'    => $returnURL,
          'formErrors'    => $formErrors,
          'formData'      => $formData,
        ];

        return $this->render('whitepapers/request.html.twig', $data);
    }

    public function show($id, $slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $whitepaper = $this->api->getPageByUrl('/news/whitepapers/' . $slug);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Whitepaper not found', $e);
        }

        return $this->render('whitepapers/confirmation.html.twig', [
            'whitepaper' => $whitepaper
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
