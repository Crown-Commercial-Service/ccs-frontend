# Website security

We follow security best practises when developing the CCS website, a summary of how we have tackled security appears below.

## Low attack vector

Since we have used a Headless CMS architecture there is dramatically less functionality exposed in the CCS website than 
for a traditional WordPress-powered website. Only functionality required for the CCS website project has been developed, 
which allows the development team to more easily tailor security requirements for each specific feature rather than rely 
on out-of-the box functionality from plugins which are harder to validate security for.

## Filter input, escape output

Incoming variables are filtered via PHP's standard [filter_var](https://www.php.net/filter_var) functionality.

The majority of content passed to the template layer is [automatically escaped](https://twig.symfony.com/doc/2.x/filters/escape.html) 
by the Twig templating system. Twig applies automatic HTML escaping and can be configured to escape content for different 
contexts (e.g. URLs, CSS, JavaScript).

Rich text content from WordPress which contains HTML cannot be escaped and is displayed raw (e.g. page content).

## Prepared statements

Queries run against the database use prepared statements to ensure variables are properly escaped to avoid dangerous characters 
being passed to the database. We use standard  [PDO prepared statements](https://www.php.net/pdo.prepared-statements) for this.

## Secrets not in version control

Any sensitive credentials are stored in environment variables which are stored in private, encrypted-at-rest AWS S3 buckets and copied to the 
webservers on deployment.

## Email

No emails are sent from the frontend web application. 