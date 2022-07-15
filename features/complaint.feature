Feature: Buyer or supplier submits Contact form
  As a buyer or supplier
  I want to be able to complete and submit the Contact form
  So that I know my voice has been heard

  Scenario: Submits complaint
    Given I am on "/contact"
    Then I should see "Contact CCS"
    When I select "Complaint" from "Type of enquiry"
    When I fill in "Name" with "Behat Testerson"
    When I fill in "Email" with "carlos@Strata.net"
    When I press "Send enquiry"
    Then I should see "Your message has been sent"
    And I should see "We will respond in 2 working days"

  Scenario: Submits general enquiry
    Given I am on "/contact"
    Then I should see "Contact CCS"
    When I select "General enquiry" from "Type of enquiry"
    When I fill in "Name" with "Behat Testerson"
    When I fill in "Email" with "carlos@Strata.net"
    When I press "Send enquiry"
    Then I should see "Your message has been sent"
    And I should see "If you don't hear from us after 7 working days"
