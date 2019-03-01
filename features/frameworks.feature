Feature: Buyer views list of Frameworks
  As a buyer
  I want to be able to see a list of 'Live' and 'Expired - data still received' Frameworks
  So that I can start my procurement process

  Scenario: Follows link
    Given I am on "/"
    When I follow "Search frameworks"
    Then I should be on "/frameworks"
    And I should see "Search frameworks"

  Scenario: Browse a category
    Given I am on "/frameworks"
    And I should see "Browse by category"
    When I follow "Construction"
    Then I should be on "/frameworks/category/construction"
    And I should see "Frameworks in the Construction category"

  Scenario: Browse a pillar
    Given I am on "/frameworks"
    And I should see "Browse by category"
    When I follow "Corporate Services"
    Then I should be on "/frameworks/pillar/corporate-services"
    And I should see "Frameworks in the Corporate Services category"
