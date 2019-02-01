Feature: Test
  In order to deploy the CCS project
  As a developer
  I need to be able to test Behat works

  Scenario: Home page loads
    Given I am on "/"
    Then I should see "CCS Page Template frontend test"

  Scenario: Follow a link
    Given I am on "/"
    When I follow "Contact"
    Then I should be on "/template/contact"
    And I should see "Test content"
