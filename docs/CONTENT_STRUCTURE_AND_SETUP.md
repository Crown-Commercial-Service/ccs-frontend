# Content Structure and Setup

The content structure and setup is generally defined in the `config` directory.

## Configuring Which Content to Pull Through from WordPress

The site content structure is configured in the following file:

`config/content/content-model.yaml`

This essentially configures all of the custom content types for the site, and points to 
the relevant endpoints in WordPress to retrieve the data.

For example:

```
events:
  api_endpoint: wp/v2/event
  content_fields: events.yaml
  source_content_type: event
```

This configured a content of type 'event'. The data for this content type is coming from the `wp/v2/event` 
endpoint in WordPress, and the fields for this content type are defined in the `events.yaml` config file.

It is generally a good idea to specify `source_content_type`, as this can be useful to know in other parts of the codebase.

`source_content_type` essentially defineds the name of the content type in WordPress (in this case `event`) as this can't 
necessarily be inferred from the Rest API.

You can read more about the WordPress Rest API in [its dedicated documentation page](WORDPRESS_REST_API.md).

### Content Fields

In the above example you can see the content fields file has been defined as `events.yaml`. This defines which fields from the 
WordPress Rest API you would like to make accessible in this frontend repo.

An example from the `events.yaml` config file is:

```
cta_destination:
  type: text
start_datetime:
  type: datetime
end_datetime:
  type: datetime
```

If you look at the WordPress Rest API endpoint for this content type (`wp/v2/event`) then you should see these fields are all defined 
in the response for each item.

These fields are defined under `acf` in the response, which stands for Advanced Custom Fields. This is [a popular Wordpress Plugin](https://www.advancedcustomfields.com/) 
that is used within WordPress to add custom fields to content types. There is no need to add an `acf` item in the content fields config files 
as the Frontend project checks for fields under `acf` in Rest API responses if it finds no match in the main response body.

### Further Documentation

You can read more about the content structure and different types of content available in the Strata/Frontend documentation:

[https://github.com/strata/frontend/blob/master/docs/content-model.md](https://github.com/strata/frontend/blob/master/docs/content-model.md)

## Routing

Routing for the project is defined in:

```config/routes.yaml```

THe routing system used is the Symfony Routing system. You can read more about how this works etc. in the official documentation for the project:

[https://symfony.com/doc/current/routing.html](https://symfony.com/doc/current/routing.html)

### Controllers

The routing configures which controllers handle requests for which URL requests.

The Controllers for the project live here:

```src/Controller```

In the constructor of each controller, you define the relevant content type for the page, and also the cache lifetime for the page, e.g.

```
$this->api = new Wordpress(
    getenv('APP_API_BASE_URL'),
    new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
);
$this->api->setContentType('events');
$this->api->setCache($cache);
$this->api->setCacheLifetime(300);
```

This will set the content type to 'events' and set the cache lifetime for pages served by this controller to 300 seconds (5 minutes).

You can read more about caching in [its dedicated documentation page](CACHING.md).
