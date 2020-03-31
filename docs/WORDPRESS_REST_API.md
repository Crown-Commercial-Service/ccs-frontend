# WordPress Rest API

[WordPress comes with a Rest API build in by default.](https://developer.wordpress.org/rest-api/) You can access it via:

[cms-url]/wp-json/wp/v2/
 
The frontend receives data via this Rest API. You can view all of the endpoints for the rest API by visiting: 

[cms-url]/wp-json/wp/v2/

Firefox formats this data in an easily readable format. Chrome by default outputs this data in a raw format, but you can install 
extensions to format this data for better readability, for example:

[https://chrome.google.com/webstore/detail/json-formatter/](https://chrome.google.com/webstore/detail/json-formatter/)

It is strongly recommended that you have some easy and clear way of reading the data from this URL as it is key to understanding the 
data that is coming from WordPress.

## Custom Endpoints

There are also some custom endpoints defined for the project that exist under:

[cms-url]/wp-json/ccs/v1/ 

These endpoints are defined in the WordPress repo, in the CCS Custom plugin. 

You should be able to view the code in the WordPress repo at:

```public/wp-content/plugins/ccs-custom/library/rest-api```

(Please reference the ccs-wordpress documentation for further information about this).
