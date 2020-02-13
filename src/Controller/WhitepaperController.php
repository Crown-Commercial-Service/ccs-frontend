<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\Cms\RestData;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\PaginationException;
use Studio24\Frontend\Exception\WordpressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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
        $this->api->setCacheLifetime(300);
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

        $data = [
          'whitepaper'    => $whitepaper,
          'campaign_code' => $whitepaper->getContent()->get('campaign_code') ? $whitepaper->getContent()->get('campaign_code')->getValue() : '',
          'form_action'   => getenv('APP_BASE_URL') === 'prod' ? 'https://webto.salesforce.com/servlet/servlet.WebToCase?encoding=UTF-8' : 'https://crowncommercial--preprod.cs86.my.salesforce.com/servlet/servlet.WebToCase?encoding=UTF-8',
          'description'   => $whitepaper->getContent()->get('description') ? $whitepaper->getContent()->get('description')->getValue() : '',
          'return_url'    => getenv('APP_BASE_URL') . '/whitepaper/confirmation/' . $whitepaper->getId() . '/' . $whitepaper->getUrlSlug() . '/?' . filter_var($_SERVER['QUERY_STRING'], FILTER_SANITIZE_STRING)
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
}
