
Feature: fitlering agreements
  As a user of the CSS website  
  In order to see the agreements I want to see I need to

  Scenario: view all agreements
    Given I am on "/agreements"
    When I click the link "Live"
    Then the "all" checkbox should be checked
    And the "live" checkbox should be unchecked
    And the "upcoming" checkbox should be unchecked
    And the "expired" checkbox should be unchecked

  Scenario: view live agreements only
    Given I am on "/agreements"
    And I click the link "Live"
    When I check "live"
    And wait for the page to be loaded
    Then the "all" checkbox should be unchecked
    And the "live" checkbox should be checked
    And the "upcoming" checkbox should be unchecked
    And the "expired" checkbox should be unchecked
    And I should not see a ".ccs-tag--error" element

  Scenario: view upcoming agreements only
    Given I am on "/agreements"
    And I click the link "Live"
    Then I check "upcoming"
    And wait for the page to be loaded
    And the "all" checkbox should be unchecked
    And the "live" checkbox should be unchecked
    And the "upcoming" checkbox should be checked
    And the "expired" checkbox should be unchecked
    And I should not see a ".ccs-tag--error" element
    And I should see a ".govuk-tag--subtle" element

  Scenario: view expired agreements only
    Given I am on "/agreements"
    And I click the link "Live"
    Then I check "expired"
    And wait for the page to be loaded
    And the "all" checkbox should be unchecked
    And the "live" checkbox should be unchecked
    And the "upcoming" checkbox should be unchecked
    And the "expired" checkbox should be checked
    And I should see "Expired" in the ".ccs-tag--error" element
    And I should not see a ".govuk-tag--subtle" element
    
  Scenario: view all agreements from checking all the checkboxes
    Given I am on "/agreements"
    And I click the link "Live"
    When I check "live"
    And wait for the page to be loaded
    And I check "upcoming"
    And wait for the page to be loaded
    And I check "expired"
    And wait for the page to be loaded
    Then the "all" checkbox should be checked
    And the "live" checkbox should be unchecked
    And the "upcoming" checkbox should be unchecked
    And the "expired" checkbox should be unchecked

  Scenario: browse second page
    Given I am on "/agreements"
    When I navigate to page "2"
    Then I should be on "/agreements/search/2"

  Scenario: browse a category
    Given I am on "/agreements"
    And I should see "Browse by category"
    When I click the link "Construction"
    Then I should be on "/agreements/category/construction"
    And I should see "Search agreements in Construction category" in the ".page-title" element

  Scenario: browse a pillar
    Given I am on "/agreements"
    And I should see "Browse by category"
    And I click the link "Corporate Solutions"
    Then I should be on "/agreements/pillar/corporate-solutions"
    And I should see "Search agreements in Corporate Solutions category" in the ".page-title" element

  Scenario: browse second page of pillar
    Given I am on "/agreements"
    And I click the link "Corporate Solutions"
    When I navigate to page "2"
    Then I should be on "agreements/search/2?pillar=corporate-solutions"