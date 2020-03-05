# Data Modelling

This document aims to outline the core functionality the CCS website has with regards to search and Elasticseach

## Data entities

This documentation covers the following entities

- Frameworks
- Lots
- Suppliers

## Master data

Master data is located in Salesforce.

## Data path

1. Data originates in Salesforce
2. Data is then imported into a custom database
3. Links to this data are created in Wordpress
4. Wordpress authors create additional data which gets added to the custom database alongside the original entries.
5. Data is served over the API from the custom database

## What data is created in Salesforce and what data is created in Wordpress

In the essence of keeping this information up to date, please refer to the specific entity repository files located here:

- src/App/Repository/FrameworkRepository.php
- src/App/Repository/LotRepository.php
- src/App/Repository/SupplierRepository.php

The property `$databaseBindings` lists all the fields which are input from external information.

The fields Wordpress creates (if any) are listed in the property `$wordpressDataFields` - it is safe to assume everything not listed in `$wordpressDataFields` originates from Salesforce.
