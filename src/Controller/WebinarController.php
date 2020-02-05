<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\PaginationException;
use Studio24\Frontend\Exception\WordpressException;
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
        $this->api->setCacheLifetime(1800);
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

        $data = [
          'webinar'       => $webinar,
          'campaign_code' => $webinar->getContent()->get('campaign_code') ? $webinar->getContent()->get('campaign_code')->getValue() : '',
          'form_action'   => getenv('APP_BASE_URL') === 'prod' ? 'https://webto.salesforce.com/servlet/servlet.WebToCase?encoding=UTF-8' : 'https://crowncommercial--preprod.cs86.my.salesforce.com/servlet/servlet.WebToCase?encoding=UTF-8',
          'description'   => $webinar->getContent()->get('description') ? $webinar->getContent()->get('description')->getValue() : '',
          'return_url'    => getenv('APP_BASE_URL') . '/webinar/confirmation/' . $webinar->getId() . '/' . $webinar->getUrlSlug() . '/'
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
