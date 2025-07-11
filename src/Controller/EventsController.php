<?php

declare(strict_types=1);

namespace App\Controller;

use App\Helper\ControllerHelper;
use Error;
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
        if ($page == 1) {
            $requestedPage = (int) filter_var($request->query->get('page'), FILTER_SANITIZE_NUMBER_INT);
            $page  = $requestedPage != 0 ? $requestedPage : 1;
        } else {
            $page = intval(filter_var($page, FILTER_SANITIZE_NUMBER_INT));
        }

        $this->api->setCacheKey($request->getRequestUri());

        //Get taxonomies for filtering option
        $audienceTag        = $this->api->getAllTerms('audience_tag');
        $eventType          = $this->api->getAllTerms('event_type');
        $productsServices   = $this->api->getAllTerms('products_services');
        $sectors            = $this->api->getAllTerms('sectors');

        //Get taxonomies that user has selected
        $audienceTagFilter      = ControllerHelper::converArrayToStringForWordpress($request->query->get('audience_tag'), $audienceTag != null ? $audienceTag->count() : null);
        $eventTypeFilter        = ControllerHelper::converArrayToStringForWordpress($request->query->get('event_type'), $eventType != null ? $eventType->count() : null);
        $productServiceFilter   = ControllerHelper::converArrayToStringForWordpress($request->query->get('product_service'), $productsServices != null ? $productsServices->count() : null);
        $sectorFilter           = ControllerHelper::converArrayToStringForWordpress($request->query->get('sector'), $sectors != null ? $sectors->count() : null);

        //Define options for Rest API query and check if view all option has been checked
        $options = [
            'audience_tag'          => $request->query->get('allAudience') != null ? null : $audienceTagFilter,
            'event_type'            => $request->query->get('allType') != null ? null : $eventTypeFilter,
            'products_services'     => $request->query->get('allPS') != null ? null : $productServiceFilter,
            'sectors'               => $request->query->get('allSectors') != null ? null : $sectorFilter,
            'orderby'               => 'start_datetime',
            'order'                 => 'asc',
        ];

        try {
            $list = $this->api->listPages($page, $options);
        } catch (NotFoundException | PaginationException $e) {
            throw new NotFoundHttpException('Events page not found', $e);
        }

        return $this->render('events/list.html.twig', [
            'api_base_url'          => getenv('SEARCH_API_BASE_URL'),
            'app_base_url'          => getenv('APP_BASE_URL'),
            'url'                   => sprintf('/events/page/%s', $page),
            'events'                => $list,
            'pagination'            => $list->getPagination(),
            'pageNumber'            => $page,
            'sectors'               => $sectors,
            'audience_tag'          => $audienceTag,
            'event_type'            => $eventType,
            'products_services'     => $productsServices,
            'filters'               => $options
        ]);
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
        $sanitisedSlug = filter_var($slug, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $this->api->setCacheKey($request->getRequestUri());

        try {
            $event = $this->api->getPage((int) $id);
        } catch (Error) {
            return $this->render('events/error.html.twig');
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
            'content_group' => $content_group ?? null,
        ];

        return $this->render('events/show.html.twig', $data);
    }

    private function createEventSchemaJSON($event)
    {
        $content = [$event->getContent()];

        $online = [
            "@type" => "VirtualLocation",
            "url" => ($content[0]['cta_destination']->getValue())
        ];

        $inPerson = [];
        if (array_key_exists('place_name', $content[0])) {
            $inPerson = [
                "@type" => "Place",
                "name" =>  $content[0]['place_name']->getValue(),
                "address" => [
                    "@type" => "PostalAddress",
                    "streetAddress" => $content[0]['street_address']->getValue(),
                    "addressLocality" => $content[0]['address_locality']->getValue(),
                    "postalCode" => $content[0]['postal_code']->getValue(),
                    "addressRegion" => $content[0]['address_region']->getValue(),
                    "addressCountry" => $content[0]['address_country']->getValue()
                ]
            ];
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
                $location = [$online, $inPerson];
                break;
        }

        $schema = [
            "@context" => "https://schema.org",
            "@type" => "Event",
            "name" => $event->getTitle(),
            "startDate" => date_format($content[0]['start_datetime']->getValue(), 'c'),
            "endDate" => date_format($content[0]['end_datetime']->getValue(), 'c'),
            "eventAttendanceMode" => $eventAttendanceMode,
            "eventStatus" => "https://schema.org/EventScheduled",
            "location" => $location,
            "image" => $event->getFeaturedImage(),
            "description" => strip_tags((string) $content[0]['description']->getValue()),
            "organizer" => [
                "@type" => "Organization",
                "name" => "Crown Commercial Service",
                "url" => "https://www.crowncommercial.gov.uk/"
            ]
        ];

        return json_encode($schema);
    }
}
