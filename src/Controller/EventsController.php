<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\ControllerHelper;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;
use Strata\Frontend\Cms\Wordpress;
use Strata\Frontend\ContentModel\ContentModel;
use Strata\Frontend\Exception\PaginationException;
use Strata\Frontend\Exception\WordpressException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Strata\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventsController extends AbstractController
{
    /**
     * Events Rest API data
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
        $this->api->setContentType('events');
        $psr16Cache = new Psr16Cache($cache);
        $this->api->setCache($psr16Cache);
        $this->api->setCacheLifetime(900);
    }

    public function list(Request $request, $page = 1)
    {
        $page = (int) filter_var($page, FILTER_SANITIZE_NUMBER_INT);

        $this->api->setCacheKey($request->getRequestUri());

        /**
         * Get taxonomies for filtering results
         */
        $sectors = $this->api->getAllTerms('sectors');
        $productsServices = $this->api->getAllTerms('products_services');
        $audienceTag = $this->api->getAllTerms('audience_tag');
        $eventType = $this->api->getAllTerms('event_type');

        $eventTypeFilter    = $request->query->get('event_type');
        $audienceTagFilter    = $request->query->get('audience_tag');
        $productServiceFilter = $request->query->get('product_service');
        $sectorFilter         = $request->query->get('sector');

        /**
         * Define options for Rest API query
         */
        $options = [
            'products_services' => $productServiceFilter,
            'audience_tag' => $audienceTagFilter,
            'event_type'    => $eventTypeFilter,
            'sectors' => $sectorFilter,
            'orderby'    => 'start_datetime',
            'order'      => 'asc',
        ];

        try {
            $list = $this->api->listPages($page, $options);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Events page not found', $e);
        }

        if (getenv('APP_API_BASE_URL') == 'prod') {
            return $this->render('events/list.html.twig', [
                'url' => sprintf('/events/page/%s', $page),
                'events' => $list,
                'pagination' => $list->getPagination(),
                'sectors' => $sectors,
                'audience_tag' => $audienceTag,
                'event_type' => $eventType,
                'products_services' => $productsServices,
                'filters' => $options
            ]);
        } else {
            // This is for DEV and UAT
            return $this->render('events/list_with_JS.html.twig', [
                'url' => sprintf('/events/page/%s', $page),
                'events' => $list,
                'pagination' => $list->getPagination(),
                'sectors' => $sectors,
                'audience_tag' => $audienceTag,
                'event_type' => $eventType,
                'products_services' => $productsServices,
                'filters' => $options
            ]);
        }
    }

    /**
     * @param $id
     * @param $slug
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Strata\Frontend\Exception\ContentFieldException
     * @throws \Strata\Frontend\Exception\ContentTypeNotSetException
     * @throws \Strata\Frontend\Exception\FailedRequestException
     * @throws \Strata\Frontend\Exception\PermissionException
     */
    public function show($id, $slug, Request $request)
    {
        $sanitisedSlug = filter_var($slug, FILTER_SANITIZE_STRING);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $event = $this->api->getPage((int) $id);
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Event not found', $e);
        }

        if (isset($event->getContent()["sectors"])) {
            $listOfSector = $event->getContent()["sectors"]->getValue();
            $content_group = ControllerHelper::toSlugList($listOfSector, "events/");
        }

        $data = [
            'event'         => $event,
            'schema'        => $this->createEventSchemaJSON($event),
            'content_group' => isset($content_group) ? $content_group : null,
        ];

        return $this->render('events/show.html.twig', $data);
    }

    private function createEventSchemaJSON($event)
    {
        $content = array($event->getContent());

        $online = array(
            "@type" => "VirtualLocation",
            "url" => ($content[0]['cta_destination']->getValue())
        );

        $inPerson = [];
        if (array_key_exists('place_name', $content[0])) {
            $inPerson = array(
                "@type" => "Place",
                "name" =>  $content[0]['place_name']->getValue(),
                "address" => array(
                    "@type" => "PostalAddress",
                    "streetAddress" => $content[0]['street_address']->getValue(),
                    "addressLocality" => $content[0]['address_locality']->getValue(),
                    "postalCode" => $content[0]['postal_code']->getValue(),
                    "addressRegion" => $content[0]['address_region']->getValue(),
                    "addressCountry" => $content[0]['address_country']->getValue()
                )
            );
        }

        $location = [];
        $eventAttendanceMode = '';

        switch ($content[0]['location_type']) {
            case "In Person":
                $eventAttendanceMode = "https://schema.org/OfflineEventAttendanceMode";
                $location = $inPerson;
                break;
            case "Online":
                $eventAttendanceMode = "https://schema.org/OnlineEventAttendanceMode";
                $location = $online;
                break;
            case "Online and In Person":
                $eventAttendanceMode = "https://schema.org/MixedEventAttendanceMode";
                $location = array($online, $inPerson);
                break;
        }

        $schema = array(
            "@context" => "https://schema.org",
            "@type" => "Event",
            "name" => $event->getTitle(),
            "startDate" => date_format($content[0]['start_datetime']->getValue(), 'c'),
            "endDate" => date_format($content[0]['end_datetime']->getValue(), 'c'),
            "eventAttendanceMode" => $eventAttendanceMode,
            "eventStatus" => "https://schema.org/EventScheduled",
            "location" => $location,
            "image" => $event->getFeaturedImage(),
            "description" => strip_tags($content[0]['description']->getValue()),
            "organizer" => array(
                "@type" => "Organization",
                "name" => "Crown Commercial Service",
                "url" => "https://www.crowncommercial.gov.uk/"
            )
        );

        return json_encode($schema);
    }
}
