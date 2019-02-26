<?php

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\ContentModel\ContentModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Studio24\Frontend\Cms\RestData;

class FrameworksController extends AbstractController
{

    /**
     * Frameworks Rest API data
     *
     * @var RestData
     */
    protected $api;

    public function __construct(CacheInterface $cache)
    {
        $this->api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/packages/content/content-model.yaml')
        );
        $this->api->setContentType('frameworks');
        $this->api->setCache($cache);
    }

    /**
     * List frameworks
     *
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function list(int $page = 1)
    {
        $results = $this->api->list($page);
        $data = [
            'pagination' => $results->getPagination(),
            'results'    => $results
        ];
        dump($results);

        return $this->render('frameworks/list.html.twig', $data);
    }

    /**
     * List frameworks by category
     *
     * @param string $category
     * @param int $page
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PaginationException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function listByCategory(string $category, int $page = 1)
    {
        // @todo map category slug to category name in DB
        
        $results = $this->api->list($page, ['category' => $category]);

        $data = [
            'category'      => $category,
            'pagination'    => $results->getPagination(),
            'results'       => $results
        ];
        return $this->render('frameworks/list-category.html.twig', $data);
    }


    /**
     * Show one framework
     *
     * @param string $rmNumber
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Studio24\Frontend\Exception\ContentFieldException
     * @throws \Studio24\Frontend\Exception\ContentFieldNotSetException
     * @throws \Studio24\Frontend\Exception\ContentTypeNotSetException
     * @throws \Studio24\Frontend\Exception\FailedRequestException
     * @throws \Studio24\Frontend\Exception\PermissionException
     */
    public function show(string $rmNumber)
    {
        $results = $this->api->getOne($rmNumber);
        $data = [
            'framework' => $results
        ];
        return $this->render('frameworks/show.html.twig', $data);
    }

}