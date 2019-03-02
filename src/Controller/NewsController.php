<?php
declare(strict_types=1);

namespace App\Controller;

use Psr\SimpleCache\CacheInterface;
use Studio24\Frontend\Cms\Wordpress;
use Studio24\Frontend\Exception\WordpressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class NewsController extends AbstractController
{
    /**
     * Frameworks Rest API data
     *
     * @var RestData
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
    }

    public function list($page = 1)
    {
        echo "FISH";

        $list = $this->wp->listPages($page);

        // Cache page IDs
        // @todo refactor for re-use
        $pageIds = [];
        foreach ($list as $item) {
            $pageIds[$item->getUrlSlug()] = $item->getId();
        }
        $cache->set('pageIds', $pageIds);

        return $this->render('news/list.html.twig', [
            'url' => sprintf('/news/page/%s', $page),
            'pages' => $list
        ]);
    }

    public function show($slug)
    {
        // Get page IDs
        // @todo refactor to lazy-load since will fail if this does not exist
        $pageIds = $cache->get('pageIds');
        if (!isset($pageIds[$slug])) {
            throw new WordpressException(sprintf('Cannot find page ID for slug %s', $slug));
        }
        $id = $pageIds[$slug];
        $page = $this->wp->getPage($id);

        return $this->render('news/show.html.twig', [
            'url' => sprintf('/news/%s', $slug),
            'page' => $page
        ]);
    }

}