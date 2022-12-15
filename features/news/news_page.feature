Feature: getting to news page
  As a user of the CSS website  
  In order to see the content I want to see I need to

  Scenario: Follows link from homepage
    Given I am on "/"
    When I click the link "News"
    Then I should be on "/news/?&page=1"
    And I should see "Explore our latest news"
    And I should see "Alongside our latest news you will find blogs, case studies, whitepapers and recorded webinars here. Filter by area of interest, sector and content type to help you find what interests you most."