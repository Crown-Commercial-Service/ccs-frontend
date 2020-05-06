##### Setting up on PC rather than Linux or Mac

###### prerequisites

- Php7.3 or greater along with any extensions it requires.

- XAMPP or similar solution stack such as WAMP or LAMP 

- mysql 5.7.30 or greater

- elasticsearch 7.6.2

###### Wordpress

- git clone https://github.com/Crown-Commercial-Service/ccs-wordpress.git

- move the project into htdocs of XAMPP

- change the httpd.conf files to point to the directories and set up necessary virtual hosts 

- export a recent database data dump and import it into your local database

- modify your .env file with the relevant fields

- run apache and elastic search

- try and access the Wordpress admin site if you can then the Wordpress site is working


###### Salesforce

- in public folder run these commands they index the searches and import the necessary data from salesforce, use these if `wp salesforce import all` doesn’t work

wp salesforce import updateFrameworkSearchIndex
wp salesforce import updateSupplierSearchIndex

###### Frontend

- git clone https://github.com/Crown-Commercial-Service/ccs-frontend.git

- move the project into htdocs of XAMPP

- change the http.conf files to point to the directories and set up necessary virtual hosts

- modify your .env file with the relevant fields

- run the apache and elastic search make sure the Wordpress is working and running

- try access the front end site if its displaying then both the Wordpress and frontend are working

###### Troubleshooting

- if the frontend errs and displays generic errors, remove the try catch in the frontend controllers this will allow the full stack trace to be display

- if errs are due to environment variables check they completely correct and there are no spelling mistakes or incorrect structures

- if environment variables are still the issue do a variable dump to the determine if it’s picking up the environment variables from the system or the .env file if its picking up the system variables you may need a library to force the use of the .env file recommended as it has worked with the project is dotenv

###### Access Frontend and Wordpress URL in local 

- to test that the frontend and wordpress are communicating together run the follwoing URL:
    - Frontend URL: http://local.crowncommercial.gov.uk
    - Wordpress URL: http://ccs-agreements.cabinetoffice.localhost
    - Wordpress Dashboard URL for admin use: http://ccs-agreements.cabinetoffice.localhost/ccswebadmin/