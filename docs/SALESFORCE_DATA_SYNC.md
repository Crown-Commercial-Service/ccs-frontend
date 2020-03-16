# Salesforce to Website data sync process

The core content of the website runs off data stored on [Salesforce](https://www.salesforce.com). Data is fetched from Salesforce, manipulated and stored in such a way so that the website can easily consume it.

## The data we are interested in

Predominantly we are interested in the following three business entities:

1. Frameworks
2. Lots
3. Suppliers

To support supplier content we also fetch supplier contact details, however we will go into that in more detail later.

## The import process

### Gathering initial data

Before we start to fetch the data, we need to gather a few large datasets which we store locally to help with processing. This helps with processing speed and significantly reduces the number of API requests we have to make to Salesforce.

The initial data we retrieve are from the following Salesforce objects:

1. Contact
2. Master_Framework_Lot_Contact__c

We fetch and store every entry in these objects before running the import, *every time*.

### Importing data

#### Frameworks

The first object we fetch is Frameworks (Salesforce object: `Master_Framework__c`). We retrieve every Framework with the following criteria:

```
WHERE Don_t_publish_on_website__c = FALSE
```

We store each of these Framework's locally in a database table. We then check whether this Framework has been imported before or not. If not, we create a new post in Wordpress.

#### Lots

Once we have saved the Framework above, we fetch the Lots attached to that Framework. (Salesforce object: `Master_Framework_Lot__c`). All Lots are retrieved that match the following conditions:

```
Master_Framework_Lot_Number__c > '0'
AND
Hide_this_Lot_from_Website__c = FALSE
```

We store each Lot locally in a database table.

We create a Wordpress post for each Lot which hasn't been imported before.

#### Suppliers

For each Lot we then retrieve suppliers if the following property is *not* set on the Lot `Hide_Lot_Suppliers_from_Website__c`.

Providing this not set, we fetch all Suppliers attached to the Lot. (Salesforce object: `Account`). Suppliers are retrieved when they match the following condition:

```
Status__c = 'Live'
```

Providing the above is met, we save all suppliers in a database table.

We then create a link between this supplier and the lot (as suppliers can be on multiple lots). From this point forward, we will refer to this link as the _Lot Supplier joining table_

##### Further supplier details

We perform further queries using the inital data we gathered at the start of the import process, and the data we have already gathered to ascertain whether there are any specific contact or informational details for this lot / supplier relationship.

Once we cross reference the data, details for the following fields are input, where found:

- trading name
- contact name
- contact email

The above details are stored on the _Lot Supplier joining table_ if they exist.

### Checking Supplier Frameworks

Once the above has run through for every Framework, we check each supplier, one by one, to determine if they belong to any _live_ frameworks. If they do, we mark this column in the Supplier database. This is important for certain features of the website to ensure performance is high.

### Completing the import

We have now completed the import. A summary of the number of imported items, errors and issues are logged to the relevant log file.


## Further technical information

CLI command to run the import: `wp salesforce import all`

Location of function which this command runs: ***public function all*** `public/wp-content/plugins/ccs-salesforce/includes/cli-commands.php`
