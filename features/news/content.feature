Feature: returning the right contents
  As a user of the CSS website  
  The filter option I selected should return the right contents for me

  Scenario: read all kinds of contents
    Given I am on "/news/?&page=1"
    And the page loaded
    Then I should see "Decarbonising freight transport: how the logistics industry can use software to reduce carbon emissions"
    Then I should see "Customer newsletters for October"
    Then I should see "5 tips to help with your commercial move"
    Then I should see "Changes to our agreements in September"
    Then I should see "Aggregation helps customers make savings on Microsoft licences"
  
  Scenario: read all kinds of contents and move on to page 2
    Given I am on "/news/?&page=1"
    And the page loaded
    Then I navigate to page "2"
    When the page loaded
    Then I should see "Energy market update"
    Then I should see "Decarbonising the logistics sector: fueling a greener public sector"
    Then I should see "Demystifying the key terms and abbreviations in procurement – Procurement Essentials"
    Then I should see "Local authority partners use Digital Marketplace to deliver service transformation for management of housing repairs"
    Then I should see "Supporting the public sector to improve data use, drive efficiency and improve services"

  Scenario: read all kinds of contents in page 8
    Given I am on "/news/?&page=8"
    And the page loaded
    Then I should see "Helping the public sector meet smart transport challenges"
    Then I should see "We can help local authorities with their refugee resettlement needs"
    Then I should see "Talking Carbon Net Zero with the CCS Print category team"
    Then I should see "New IT partner helps Care Quality Commission raise user satisfaction to almost 95%"
    Then I should see "How CCS is enabling a greener supply chain"

  Scenario: read Procurement Essentials only
    Given I am on "/news/?&categories=168&page=1"
    And the page loaded
    Then I should see "Demystifying the key terms and abbreviations in procurement – Procurement Essentials"
    Then I should see "What is a Dynamic Purchasing System? – Procurement Essentials"
    Then I should see "How to carry out early market engagement successfully – Procurement Essentials"
    Then I should see "When and how to run a further competition for your goods and services – Procurement Essentials"
    Then I should see "Aggregations, eAuctions, and how to take advantage of the public sector’s national buying power – Procurement Essentials"
 
  Scenario: read Procurement Essentials only and move on to page 2 
    Given I am on "/news/?&categories=168&page=1"
    And the page loaded
    Then I navigate to page "2"
    When the page loaded
    Then I should see "Social value in procurement – Procurement Essentials"
    Then I should see "Effective Contract Management – Procurement Essentials"
    Then I should see "How to evaluate bids – Procurement Essentials"
    Then I should see "What is a framework – Procurement Essentials"
    Then I should see "How to write a specification – Procurement Essentials"

  Scenario: read whitepaper only
    Given I am on "/news/?&whitepaper=1&page=1"
    And the page loaded
    Then I should see "Spend Analysis and Recovery Services whitepaper: Protecting taxpayers’ money from incorrect payments"
    Then I should see "Financial services whitepaper: Effective solutions for a stronger future"
    Then I should see "Supplier Engagement in Facilities Management"
    Then I should see "Insurance whitepaper: How advance planning can achieve best value"
    Then I should see "Print Marketplace"

  Scenario: read whitepaper only and move on to page 2 
    Given I am on "/news/?&whitepaper=1&page=1"
    And the page loaded
    Then I navigate to page "2"
    When the page loaded
    Then I should see "Modular Buildings and Modern Methods of Construction"
    Then I should see "Legal services – 4 steps to a successful fee structure OLD"
    Then I should see "Fleet whitepaper – your guide to 2020 and beyond"
    Then I should see "Travel whitepaper – how to get the best value from your business travel"
    Then I should see "Spend Analysis & Recovery Services whitepaper – protecting taxpayers’ money from incorrect payments"

  Scenario: read webinar only
    Given I am on "/news/?&webinar=1&page=1"
    And the page loaded
    Then I should see "Energy market update"
    Then I should see "Webinar: How to complete an energy review and working with third parties"
    Then I should see "Introduction to CCS Energy"
    Then I should see "Talking Carbon Net Zero with the CCS Print category team"
    Then I should see "CNZ: episode 4: how your ICT hosting choices can reduce your carbon footprint by up to 99%"

  Scenario: read news in supplier sector
    Given I am on "/news/?&categories=26&sectors=170&page=1"
    And the page loaded
    Then I should see "Customer newsletters for October"
    Then I should see "Customer newsletters for September"
    Then I should see "Customer newsletters for August"
    Then I should see "Crown Commercial Service announces a new 1 year Memorandum of Understanding (MoU) with Salesforce"
    Then I should see "CCS proactively helping suppliers with PPN 06/21 implementation"

  Scenario: read news in supplier sector and move on to page 2 
    Given I am on "/news/?&categories=26&sectors=170&page=1"
    And the page loaded
    Then I navigate to page "2"
    When the page loaded
    Then I should see "G-Cloud computing initiative marks 10-year anniversary"
    Then I should see "Customer newsletters for July"
    Then I should see "Customer newsletters for May"
    Then I should see "Customer newsletters for April"
    Then I should see "New live supplier carbon reduction plan training"

  Scenario: read news in corporate solutions products and services
    Given I am on "/news/?&categories=26&products_services=121&page=1"
    And the page loaded
    Then I should see "Is it time to review your organisation’s printing needs?"
    Then I should see "National aggregation opportunity for distribution of the Household Support Fund and other funding initiatives"
    Then I should see "Crown Commercial Service announces a new 1 year Memorandum of Understanding (MoU) with Salesforce"
    Then I should see "Supporting the health sector and COVID-19 recovery with courier and logistics services"
    Then I should see "4 tips on how to achieve value for money on your next medical waste disposal procurement"

  Scenario: read news in corporate solutions products and services, then move on to page 2 
    Given I am on "/news/?&categories=26&products_services=121&page=1"
    And the page loaded
    Then I navigate to page "2"
    When the page loaded
    Then I should see "RM6174 and RM6175 feedback survey"
    Then I should see "New and improved payment solutions coming soon"
    Then I should see "Making every penny count on your mailing with franking services"
    Then I should see "Everything you need to know about postage price increases and how CCS can help"
    Then I should see "Get help buying for schools"

# TODO (those feature has not been developed yet):
# add whitepaper sector view
# add whitepaper products and services view

# add webinar sector view
# add webinar products and services view

# Then I should see "dadadadadadadada"

    
