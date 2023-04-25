Feature: Contact CCS form
  As a Customer
  I want to be able to complete and submit the contact CCS form

  Scenario: Follows link
    Given I am on "/"
    When I click the link "Contact"
    Then I should be on "/contact"
    And I should see "Contact CCS"
    
  Scenario: Visiting the contact page directly 
    Given I am on "/contact"
    Then I should see "Contact CCS"
    And the "General enquiry" checkbox should be checked
    And the "Feedback" checkbox is unchecked

  Scenario: Submiting an empty General enquiry
    Given I am on "/contact"
    When I click the button "Send enquiry"
    Then I should see the following error messages:
      | Enter your name                                                     |
      | Enter your job title                                                |
      | Enter an email address in the correct format, like name@example.com |
      | Enter your organisation                                             |
      | Enter more detail                                                   |
    And I should see "5" error messages

  Scenario: Submiting an empty General enquiry with callback checked
    Given I am on "/contact"
    And I check "00Nb0000009IXEg"
    When I click the button "Send enquiry"
    Then I should see the following error messages:
      | Enter your name                                                                   |
      | Enter your job title                                                              |
      | Enter an email address in the correct format, like name@example.com               |
      | Enter a telephone number, like 01632 960 001, 07700 900 982 or +44 0808 157 0192  |
      | Enter your organisation                                                           |
      | Enter more detail                                                                 |
    And I should see "6" error messages

  Scenario: Submiting a General enquiry
    Given I am on "/contact"
    When I select "Website - Enquiry" from "origin"
    And I fill in the following:
      | Name                          | Behat Tester    |
      | Email                         | tester@test.net |
      | Job title                     | tester          |
      | Organisation                  | CCS             |
      | Can you provide more detail?  | some detail     |
    When I click the button "Send enquiry"
    Then I should be on "/contact/thanks"
    And I should see "Your message has been sent" in the ".page-title" element
    And the response should contain "If you don't hear from us after 7 working days, contact us on 0345 410 2222."

  Scenario: Submiting an empty Feedback
    Given I am on "/contact"
    And I select "Website - Enquiry" from "origin"
    When I click the button "Send enquiry"
    Then I should see the following error messages:
      | Enter your name                                                     |
      | Enter your job title                                                |
      | Enter an email address in the correct format, like name@example.com |
      | Enter your organisation                                             |
      | Enter more detail                                                   |
    And I should see "5" error messages

  Scenario: Submiting an empty Feedback with callback checked
    Given I am on "/contact"
    And I check "00Nb0000009IXEg"
    When I click the button "Send enquiry"
    Then I should see the following error messages:
      | Enter your name                                                                   |
      | Enter your job title                                                              |
      | Enter an email address in the correct format, like name@example.com               |
      | Enter a telephone number, like 01632 960 001, 07700 900 982 or +44 0808 157 0192  |
      | Enter your organisation                                                           |
      | Enter more detail                                                                 |
    And I should see "6" error messages

  Scenario: Submiting a Feedback
    Given I am on "/contact"
    When I select "Website - Complaint" from "origin"
    And wait for the page to be loaded
    Then I fill in the following:
      | Name                          | Behat Tester    |
      | Email                         | tester@test.net |
      | Job title                     | tester          |
      | Organisation                  | CCS             |
      | Can you provide more detail?  | some detail     |
    And I click the button "Send enquiry"
    Then I should be on "/contact/thanks-complaint"
    And I should see "Your message has been sent" in the ".page-title" element
    And the response should contain "We will respond in 2 working days with the name of the person dealing with your complaint and any actions taken. We will communicate progress at least every 10 days until your complaint is resolved. If you don't hear from us after 2 working days, contact us on 0345 410 2222"
