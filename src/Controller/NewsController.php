<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\ControllerHelper;
use App\Utils\FrameworkCategories;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;
use Strata\Frontend\Cms\Wordpress;
use Strata\Frontend\ContentModel\ContentModel;
use Strata\Frontend\Exception\PaginationException;
use Strata\Frontend\Exception\WordpressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpClient\HttpClient;
use Strata\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var Wordpress
     */
    protected $api;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->api = new Wordpress(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
        );
        $this->api->setContentType('news');
        $psr16Cache = new Psr16Cache($cache);
        $this->api->setCache($psr16Cache);
        $this->api->setCacheLifetime(900);
    }

    public function list(Request $request, $page = 1)
    {
        if ($page == 1) {
            $requestedPage = (int) filter_var($request->query->get('page'), FILTER_SANITIZE_NUMBER_INT);
            $page  = $requestedPage != 0 ? $requestedPage : 1;
        } else {
            $page = intval(filter_var($page, FILTER_SANITIZE_NUMBER_INT));
        }

        $this->api->setCacheKey($request->getRequestUri());

        $defaultOptions = [
            'whitepaper'        => 1,
            'webinar'           => 1,
            'per_page'          => 5,
            'digitalDownload'   => $this->formatIdFromObject($this->api->getAllTerms('content_type')),
        ];

        $categoriesOption       =   ControllerHelper::converArrayToStringForWordpress($request->query->get('categories', null), null);
        $downloadableOption     =   ControllerHelper::converArrayToStringForWordpress($request->query->get('digitalDownload', null), null);

        $sectorsOption          =   ControllerHelper::converArrayToStringForWordpress($request->query->get('sectors', null), null);
        $productsServicesOption =   ControllerHelper::converArrayToStringForWordpress($request->query->get('products_services', null), null);


        $options = [
            'categories'        => $categoriesOption,
            'noPost'            => $categoriesOption == null ? 1 : 0,
            'sectors'           => $sectorsOption,
            'products_services' => $productsServicesOption,
            'whitepaper'        => $request->query->get('whitepaper', null),
            'webinar'           => $request->query->get('webinar', null),
            'per_page'          => 5,
            'digitalDownload'   => $downloadableOption,
        ];


        $options = $this->prepareOptionForWordpress($options, $defaultOptions, $request);

        try {
            $list = $this->api->listPages($page, $options);
            $list->getPagination()->setResultsPerPage(5);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('News page not found', $e);
        }

        $options = $options == $defaultOptions ? $this->resetCategoriesOption($options) : $options;

        return $this->render('news/list.html.twig', [
            'url'                       => sprintf('/news/page/%s', $page),
            'api_base_url'              => getenv('SEARCH_API_BASE_URL'),
            'app_base_url'              => getenv('APP_BASE_URL'),
            'pageNumber'                => $page,
            'categoriesFilters'         => $this->api->getAllTerms('categories'),
            'sectorsFilters'            => $this->api->getAllTerms('sectors'),
            'productsServicesFilters'   => $this->api->getAllTerms('products_services'),
            'contentTypeFilters'        => $this->api->getAllTerms('content_type'),
            'pagination'                => $list->getPagination(),
            'pages'                     => $list,
            'filters'                   => $options
        ]);
    }

    public function show($slug, Request $request)
    {
        $slug = filter_var($slug, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $page = $this->api->getPageByUrl($request->getRequestUri());
            $response = HttpClient::create()->request('GET', getenv('APP_API_BASE_URL') . 'wp/v2/posts/' . $page->getId());
            if ($response->getStatusCode() == 200) {
                $acfContent = (array) json_decode($response->getContent())->acf;
                $authorText = array_key_exists('author_name_text', (array)$acfContent) ? $acfContent['author_name_text'] : null;
                $authorImage = array_key_exists('author_image', (array)$acfContent) ? $acfContent['author_image'] : null;
            }
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('News page not found', $e);
        }

        if (isset($page->getContent()["sectors"])) {
            $listOfSector = $page->getContent()["sectors"]->getValue();
            $content_group = ControllerHelper::toSlugList($listOfSector, "news/");
        }

        $featureNewsProperties = ControllerHelper::extractFeatureNewsProperties('post_components_rows', $page->getContent());

        return $this->render('news/show.html.twig', [
            'url'           => sprintf('/news/%s', $slug),
            'page'          => $page,
            'authorText'    => $authorText,
            'authorImage'   => $authorImage,
            'site_base_url' => getenv('APP_BASE_URL'),
            'content_group' => $content_group ?? null,
            'featureNewsProperties' => $featureNewsProperties,
        ]);
    }

    private function formatIdFromObject($filtersOption)
    {
        $returnArray = [];

        foreach ($filtersOption as $each) {
            $returnArray[] = $each->getId();
        };

        return implode(',', $returnArray);
    }

    private function prepareOptionForWordpress(array $options, array $defaultOptions, $request)
    {

        $options = $request->query->get('allCategories') != null ? $this->resetCategoriesOption($options) : $options;
        $options["sectors"] = ($request->query->get('allSectors') != null) ? null : $options["sectors"];
        $options["products_services"] = ($request->query->get('allPS') != null) ? null : $options["products_services"];

        $allEmpty = true;

        $checkType = [
            'categories',
            'sectors',
            'PandS',
            'content_type',
            'whitepaper',
            'webinar',
            'digitalDownload'
        ];

        foreach ($checkType as $each) {
            if (!empty($request->query->get($each))) {
                $allEmpty = false;
                break;
            }
        }

        if ($allEmpty) {
            $options = $defaultOptions;
        }

        return $options;
    }

    private function resetCategoriesOption($options)
    {
        unset($options["categories"]);
        unset($options["noPost"]);
        unset($options["whitepaper"]);
        unset($options["webinar"]);
        unset($options["digitalDownload"]);

        return $options;
    }
}
