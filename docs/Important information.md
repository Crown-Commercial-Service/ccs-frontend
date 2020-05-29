##### Important information

- S3 bucket contains the environment variables

- S3 bucket also holds all the media offload. warning the media offload can only work with whitelisted IPs so capabilities will be limited on local!

- Always isolate the code by deactivating plugins

###### The recipe for creating page components: 

- Create the definition in the fewbricks definition section of the Wordpress backend. See the other few brick component definitions to see how to make one. See the ACF website to understand the custom fields https://www.advancedcustomfields.com/ WARNING!!! make sure that the fields have unique identifiers otherwise will cause major issues. 

- In the group-page-content-default file add the new component also add a unique identifier for this.

- In the front end add necessary html in the _components directory ie the template of the component and the html as well as modifying the component.html to show the component.

- In the flexible-components.yaml add the data anchors

- Styling is done with scss see other stylesheets in the project for examples how its done




