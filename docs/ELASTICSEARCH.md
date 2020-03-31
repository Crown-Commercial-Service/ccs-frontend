# CCS ElasticSearch Documentation

This document aims to outline the core functionality the CCS website has with regards to search and Elasticseach

## Packages

[Elastica](https://github.com/ruflin/Elastica) - This package is used to bridge the gap between ElasticSearch and PHP.



## Index time

**Suppliers** are indexed after everything has been imported and saved, towards the end of a successful import run.

**Frameworks** are indexed in the import loop, one by one, after each Framework has been imported/updated.



## Manually indexing

Suppliers and Frameworks can be indexed via the WordPress repo.

Please find documentation on how to do this in [the WordPress docs](https://github.com/Crown-Commercial-Service/ccs-wordpress/blob/master/docs/ELASTICSEARCH.md).

## Implementing the search API in the front-end application

Create a new **searchApi** property in the **__construct** method of the control which requires it like so:

```
$this->searchApi = new RestData(
    getenv('SEARCH_API_BASE_URL'),
    new ContentModel(__DIR__ . '/../../config/content/content-model.yaml')
);
```

Ensure the **SEARCH_API_BASE_URL** environment variable is set to something similar to http://ccs-agreements.cabinetoffice.localhost/search-api/

When you want to search, you can switch out the application **api** for the new **searchApi** you created above. E.g:

```
$results = $this->searchApi->list($page, [
    'keyword'   => $query,
    'limit'     => 20,
]);
```

﻿

## The search API endpoints

For performance reasons the search API is kept outside of Wordpress in `/search-api` and contains two php files for the framework and suppliers search. 



## Faceted search

The Supplier search currently provides the option to display faceted results for the frameworks the current results reside in. These are complete with the number of suppliers which each framework contains.

The following facets are returned with data depending on the following scenarios:

**No search query**:  
Frameworks: All Frameworks are returned  
Lots: No Lots are returned  

**Keyword search query:**  
Frameworks: All Frameworks are returned which have a supplier in the complete list of returned results  
Lots: No Lots are returned  

**Framework filtered search query:**  
Frameworks: All Frameworks are returned  
Lots: All lots are returned for the filtered framework﻿  

﻿

## Applying filters

Filter can be applied (these would usually be sent from a form) by passing a **$filters** array.
For a normal single filter, the following fields need to be passed. You can use a dot in the field to indicate a nested property:

```
$filters[] = [
  'field' => 'live_frameworks.lot_ids',
  'value' => '5412'
];
```

These filters will add extra queries to the data and add **MUST** clauses to the ElasticSearch query.

To add a multiple nested filters with an AND query, the following is supported:

```
$nestedLiveFrameworkFilterData[] = [
   'field' => 'live_frameworks.rm_number',
   'value' => 'RM12345'
];

$nestedLiveFrameworkFilterData[] = [
   'field' => 'live_frameworks.lot_ids',
   'value' => '5412'
];

$filters['live_frameworks'] = [
   'field'     => 'live_frameworks',
   'condition' => 'AND',
   'nested'    => $nestedLiveFrameworkFilterData
];
```

The difference with aAdding these fields seperately like the below is that this would cause an OR query to be placed.

```
$filters[] = [
   'field' => 'live_frameworks.rm_number',
   'value' => 'RM12345'
];

$filters[] = [
   'field' => 'live_frameworks.lot_ids',
   'value' => '5412'
];
```



## ﻿Querying the Supplier data

The **queryByKeyword** method runs the following logic (individual queries are **OR** queries unless otherwise specified):

**Checks the search keyword against a list of synonyms:**  
A check is performed to see whether a search keyword should be converted to another string perform the search query progresses.

**Perform a fuzzy match on all fields:**  
A match is run on all of the indexed fields with a fuzziness level of 1 (meaning 1 character can be out of place at the end or start of a string).

**Boost to the name field:**  
A 2x boost is given to the supplier's **name** field if a match is found using the same settings as above (fuzziness 1).

**Perform a nested query on the supplier's live frameworks:**  
A match is run on all of the indexed fields for the supplier's live frameworks. There is no fuzziness used on this fields when searching.

﻿

## Querying the Framework data

The **queryByKeyword** method runs the following logic (individual queries are **OR** queries unless otherwise specified):

**Checks 'Published status' field:**
Performs a MUST query on the 'published_status' field for the value 'publish'

**Check on the status field:**
Performs a MUST query for one of the following two values on the 'status' field:

1. 'Live'
2. 'Expired - Data Still Received'

**Checks the search keyword against a list of synonyms:**
A check is performed to see whether a search keyword should be converted to another string perform the search query progresses.

**Perform a fuzzy match on description and summary fields:**  
A match is run on the description and summary fields with a fuzziness level of 1 (meaning 1 character can be out of place at the end or start of a string).

**Boost to the title field:**  
A 3x boost is given to the Framework's **title** field if a match is found using the same settings as above (fuzziness 1).

**Perform a variety of searches on the RM number fields:**  
The RM number field is checked for matches with or without the 'RM' prefix, and exact match, or a partial match. Exact matches are given a boost to show higher in search results.﻿



## Sorting queried data

The **queryByKeyword** method contains a method **sortQuery** which determines how the data is sorted.

By default the query will use the **_score** field to sort the data. The **_score** field is based on relevancy when a query for a keyword is run.

If the query has no keyword, the data will be ordered by default by the class property **$defaultSortField** ﻿

------
