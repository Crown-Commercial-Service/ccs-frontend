Feature: getting to agreeements page
  As a user of the CSS website  
  In order to see the agreements I want to see I need to

  Scenario: Follows link
    Given I am on "/"
    When I click the link "Search agreements"
    Then I should be on "/agreements"
    And I should see "Search agreements"
    And I should see "121 agreements found"
    And the "live" checkbox should be checked
    And the "all" checkbox should be unchecked
    And the "upcoming" checkbox should be unchecked
    And the "expired" checkbox should be unchecked
    And I should not see a ".ccs-tag--error" element