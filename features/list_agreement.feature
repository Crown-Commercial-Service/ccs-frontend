Feature: Customer views list of agreements
  As a Customer
  I want to be able to see a list of agreements
  So that I can start my procurement process

Scenario: Follows link
  Given I am on "/"
  When I click the link "Search agreements"
  Then I should be on "/agreements"
  And I should see "Search agreements"

Scenario: Visiting the agreements page directly
  Given I am on "/agreements"
  Then the "live" checkbox should be checked
  And the "all" checkbox should be unchecked
  And the "upcoming" checkbox should be unchecked
  And the "expired" checkbox should be unchecked
  And I should not see a ".ccs-tag--error" element

Scenario: View all agreements
  Given I am on "/agreements"
  When I click the link "Live"
  Then the "all" checkbox should be checked
  And the "live" checkbox should be unchecked
  And the "upcoming" checkbox should be unchecked
  And the "expired" checkbox should be unchecked

Scenario: View live agreements only
  Given I am on "/agreements"
  And I click the link "Live"
  When I check "live"
  And wait for the page to be loaded
  Then the "all" checkbox should be unchecked
  And the "live" checkbox should be checked
  And the "upcoming" checkbox should be unchecked
  And the "expired" checkbox should be unchecked
  And I should not see a ".ccs-tag--error" element

Scenario: View upcoming agreements only
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

Scenario: View expired agreements only
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
  
Scenario: View all agreements from checking all the checkboxes
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

Scenario: Browse second page
  Given I am on "/agreements"
  When I navigate to page "2"
  Then I should be on "/agreements/search/2"

Scenario: Browse a category
  Given I am on "/agreements"
  And I should see "Browse by category"
  When I click the link "Construction"
  Then I should be on "/agreements/category/construction"
  And I should see "Search agreements in Construction category" in the ".page-title" element

Scenario: Browse a pillar
  Given I am on "/agreements"
  And I should see "Browse by category"
  And I click the link "Corporate Solutions"
  Then I should be on "/agreements/pillar/corporate-solutions"
  And I should see "Search agreements in Corporate Solutions category" in the ".page-title" element

Scenario: Browse second page of pillar
  Given I am on "/agreements"
  And I click the link "Corporate Solutions"
  When I navigate to page "2"
  Then I should be on "agreements/search/2?pillar=corporate-solutions"

