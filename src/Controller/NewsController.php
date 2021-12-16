<?php

declare(strict_types=1);

namespace App\Controller;

use App\Utils\FrameworkCategories;
use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\ContentModel\ContentModel;
use Studio24\Frontend\Exception\PaginationException;
use Studio24\Frontend\Exception\WordpressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Studio24\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsController extends AbstractController
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
        $this->api->setContentType('news');
        $this->api->setCache($cache);
        $this->api->setCacheLifetime(900);
    }

    public function list(Request $request, $page = 1)
    {
        $requestedPage = (int) filter_var($request->query->get('page'), FILTER_SANITIZE_NUMBER_INT);
        $page  = $requestedPage != 0 ? $requestedPage : 1;

        $this->api->setCacheKey($request->getRequestUri());

        $categoriesFilters          = $this->api->getAllTerms('categories');
        $sectorsFilters             = $this->api->getAllTerms('sectors');
        $productsServicesFilters    = $this->api->getAllTerms('products_services');


        $selectedCategories         = $request->query->get('categories');
        $selectedSectors            = $request->query->get('sectors');
        $selectedProducts_services  = $request->query->get('products_services');


        $options = [
            'categories'        => $selectedCategories ?? null,
            'sectors'           => $selectedSectors ?? null,
            'products_services' => $selectedProducts_services ?? null,
            'per_page'          => 5,
        ];

        try {
            $list = $this->api->listPages($page, $options);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('News page not found', $e);
        }

        return $this->render('news/list.html.twig', [
            'url'                       => sprintf('/news/page/%s', $page),
            'api_base_url'              => getenv('APP_API_BASE_URL'),
            'pageNumber'                => $page,
            'categoriesFilters'         => $categoriesFilters,
            'sectorsFilters'            => $sectorsFilters,
            'productsServicesFilters'   => $productsServicesFilters,
            'title'                     => $this->getTitle($categoriesFilters, $sectorsFilters, $productsServicesFilters, $options),
            'pages'                     => $list,
            'filters'                   => $options
        ]);
    }

    public function show($slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $page = $this->api->getPageByUrl($request->getRequestUri());
            $response = HttpClient::create()->request('GET', getenv('APP_API_BASE_URL') . 'wp/v2/posts/' . $page->getId());

            if ($response->getStatusCode() == 200) {
                $authorName = json_decode($response->getContent())->authorName;
            }
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('News page not found', $e);
        }

        return $this->render('news/show.html.twig', [
            'url'           => sprintf('/news/%s', $slug),
            'page'          => $page,
            'authorName'    => $authorName
        ]);
    }

    private function getTitle($categoriesFilters, $sectorsFilters, $productsServicesFilters, $options)
    {
        $filteredId = $options['categories'] ?? $options['sectors'] ?? $options['products_services'];

        foreach ([$categoriesFilters, $sectorsFilters, $productsServicesFilters] as $filter) {
            foreach ($filter as $each) {
                if ($each->getID() == $filteredId) {
                    return $each->getName();
                }
            }
        }
    }
}
