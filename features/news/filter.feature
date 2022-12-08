Feature: fitlering the content
  As a user of the CSS website  
  In order to see the contents I want to see I need to
  I need to be able to filer down contents

  Scenario: viewing everything (default filter)
    Given I am on "/news/?&page=1"
    Then the "allCategories" checkbox should be checked
    And the "news" checkbox is unchecked
    And the "blog" checkbox is unchecked
    And the "case-study" checkbox is unchecked
    And the "procurement-essentials" checkbox is unchecked
    And the "whitepaper" checkbox is unchecked
    And the "webinar" checkbox is unchecked
    And the "allSectors" checkbox should be checked
    And the "allPS" checkbox should be checked

  Scenario: viewing news article only
    Given I am on "/news/?&page=1"
    And I check "news"
    Then the "allCategories" checkbox should be unchecked
    And the "news" checkbox is checked
    And the "blog" checkbox is unchecked
    And the "case-study" checkbox is unchecked
    And the "procurement-essentials" checkbox is unchecked
    And the "whitepaper" checkbox is unchecked
    And the "webinar" checkbox is unchecked
    And the "allSectors" checkbox should be checked
    And the "allPS" checkbox should be checked
    And the URL should contain "/news/?&categories=26&page=1"

  Scenario: viewing whitepaper article only
    Given I am on "/news/?&page=1"
    And I check "whitepaper"
    Then the "allCategories" checkbox should be unchecked
    And the "news" checkbox is unchecked
    And the "blog" checkbox is unchecked
    And the "case-study" checkbox is unchecked
    And the "procurement-essentials" checkbox is unchecked
    And the "whitepaper" checkbox is checked
    And the "webinar" checkbox is unchecked
    And the "allSectors" checkbox should be checked
    And the "allPS" checkbox should be checked
    And the URL should contain "/news/?&whitepaper=1&page=1"

  Scenario: viewing webinar article only
    Given I am on "/news/?&page=1"
    And I check "webinar"
    Then the "allCategories" checkbox should be unchecked
    And the "news" checkbox is unchecked
    And the "blog" checkbox is unchecked
    And the "case-study" checkbox is unchecked
    And the "procurement-essentials" checkbox is unchecked
    And the "whitepaper" checkbox is unchecked
    And the "webinar" checkbox is checked
    And the "allSectors" checkbox should be checked
    And the "allPS" checkbox should be checked
    And the URL should contain "/news/?&webinar=1&page=1"

  Scenario: viewing mixed article (news -> whitepaper)
    Given I am on "/news/?&page=1"
    And I check "news"
    And I check "whitepaper"
    Then the "allCategories" checkbox should be unchecked
    And the "news" checkbox is checked
    And the "blog" checkbox is unchecked
    And the "case-study" checkbox is unchecked
    And the "procurement-essentials" checkbox is unchecked
    And the "whitepaper" checkbox is checked
    And the "webinar" checkbox is unchecked
    And the "allSectors" checkbox should be checked
    And the "allPS" checkbox should be checked
    And the URL should contain "/news/?&categories=26&whitepaper=1&page=1"

  Scenario: viewing mixed article (webinar -> news)
    Given I am on "/news/?&page=1"
    And I check "webinar"
    And I check "news"
    Then the "allCategories" checkbox should be unchecked
    And the "news" checkbox is checked
    And the "blog" checkbox is unchecked
    And the "case-study" checkbox is unchecked
    And the "procurement-essentials" checkbox is unchecked
    And the "whitepaper" checkbox is unchecked
    And the "webinar" checkbox is checked
    And the "allSectors" checkbox should be checked
    And the "allPS" checkbox should be checked
    And the URL should contain "/news/?&categories=26&webinar=1&page=1"

  Scenario: viewing all articles 
    Given I am on "/news/?&page=1"
    And I check "news"
    And I check "blog"
    And I check "case-study"
    And I check "procurement-essentials"
    And I check "whitepaper"
    And I check "webinar"
    Then the "allCategories" checkbox should be checked
    And the "news" checkbox is unchecked
    And the "blog" checkbox is unchecked
    And the "case-study" checkbox is unchecked
    And the "procurement-essentials" checkbox is unchecked
    And the "whitepaper" checkbox is unchecked
    And the "webinar" checkbox is unchecked
    And the "allSectors" checkbox should be checked
    And the "allPS" checkbox should be checked
    And the URL should contain "/news/?&page=1"

  Scenario: unchecking the only selection (news)
    Given I am on "/news/?&categories=26&page=1"
    When I uncheck "news"
    Then the "allCategories" checkbox should be checked
    And the "news" checkbox is unchecked
    And the "blog" checkbox is unchecked
    And the "case-study" checkbox is unchecked
    And the "procurement-essentials" checkbox is unchecked
    And the "whitepaper" checkbox is unchecked
    And the "webinar" checkbox is unchecked
    And the "allSectors" checkbox should be checked
    And the "allPS" checkbox should be checked

  Scenario: unchecking the only selection (whitepaper)
    Given I am on "/news/?&whitepaper=1&page=1"
    When I uncheck "whitepaper"
    Then the "allCategories" checkbox should be checked
    And the "news" checkbox is unchecked
    And the "blog" checkbox is unchecked
    And the "case-study" checkbox is unchecked
    And the "procurement-essentials" checkbox is unchecked
    And the "whitepaper" checkbox is unchecked
    And the "webinar" checkbox is unchecked
    And the "allSectors" checkbox should be checked
    And the "allPS" checkbox should be checked

  Scenario: checking type filter should return to first page 
    Given I am on "/news/?&whitepaper=1&page=3"
    When I check "news"
    And the page loaded
    Then the URL should contain "/news/?&categories=26&whitepaper=1&page=1"

  Scenario: checking sector filter should return to first page 
    Given I am on "/news/?&page=3"
    When I check "supplier"
    And the page loaded
    Then the URL should contain "/news/?&sectors=170&page=1"

  Scenario: checking P_S filter should return to first page 
    Given I am on "/news/?&page=3"
    When I check "Buildings"
    And the page loaded
    Then the URL should contain "/news/?&products_services=119&page=1"