Feature: Homepage
  As a Customer
  I want to be able to visit CCS homepage and click navigate to other pages

Scenario: Visiting homepage
  Given I am on "/"
  And I should see "Digital brochure 2022-2023: procurement at your fingertips"
  And I should see "Discover our carbon net-zero and modern slavery initiatives, our commercial agreements and much more"
  And I should see "Download our brochure"

Scenario: Follows PPG create account link
  Given I am on "/"
  When I click the link "Create account"
  Then the URL should contain "https://uat.identify.crowncommercial.gov.uk/"

Scenario: Follows PPG sign in link
  Given I am on "/"
  When I click the link "Sign in"
  Then I should be on "https://auth.dev.identify.crowncommercial.gov.uk/login?state=hKFo2SB3MjF2dUx5UDZ0NDJlWlEtTVBJLWJOdlJxYkpBQ0gtcKFupWxvZ2luo3RpZNkgeV9LbkVVdWtfNndaWDFlWlJ4ZWM0c0hKcjdfOHBUV3ajY2lk2SA1RzJwTlNod2FCUXpqSk54NmZDU3F0OVpJQVU5c0V1cg&client=5G2pNShwaBQzjJNx6fCSqt9ZIAU9sEur&protocol=oauth2&response_type=code&scope=email%20profile%20openid%20offline_access&redirect_uri=https%3A%2F%2Fdev.identify.crowncommercial.gov.uk%2Fauthsuccess&code_challenge_method=S256&code_challenge=Kn0ufTg7V9VjfiMF3Ey5bsSTD4G2gwbByJikdDjXQLI"

Scenario: Follows search agreements link
  Given I am on "/"
  When I click the link "Search agreements"
  Then I should be on "/agreements"

Scenario: Follows search suppliers link
  Given I am on "/"
  When I click the link "Search suppliers"
  Then I should be on "/suppliers"

Scenario: Follows upcoming deals link
  Given I am on "/"
  When I click the link "Upcoming deals"
  Then I should be on "/agreements/upcoming"

Scenario: Follows products and services link
  Given I am on "/"
  When I click the link "Products and Services"
  Then I should be on "/products-and-services/"

Scenario: Follows sectors link
  Given I am on "/"
  When I click the link "Sectors"
  Then I should be on "/sectors"

Scenario: Follows Carbon Net Zero link
  Given I am on "/"
  When I click the link "Carbon Net Zero"
  Then I should be on "/buy-and-supply/carbon-net-zero"

Scenario: Follows about link
  Given I am on "/"
  When I click the link "About"
  Then I should be on "/about-ccs/"

Scenario: Follows contact link
  Given I am on "/"
  When I click the link "Contact"
  Then I should be on "/contact"

Scenario: Follows events link
  Given I am on "/"
  When I click the link "Events"
  Then I should be on "/events"

Scenario: Follows news link
  Given I am on "/"
  When I click the link "News"
  Then I should be on "/news/?&page=1"

Scenario: Follows Buy and supply link
  Given I am on "/"
  When I click the link "Buy and supply"
  Then I should be on "/buy-and-supply/"

Scenario: Search for an agreement
  Given I am on "/"
  When I click the button "Search agreements"
  Then I should be on "/agreements/search?q="

Scenario: Search for a supplier
  Given I am on "/"
  When I click the button "Search suppliers"
  Then I should be on "/suppliers/search?q="

Scenario: Search for an agreement (RM6100)
  Given I am on "/"
  And I fill in "RM6100" for "framework_q"
  When I click the button "Search agreements"
  Then I should be on "/agreements/search?q=RM6100"

Scenario: Search for a supplier (1-1 RECRUITMENT LIMITED)
  Given I am on "/"
  And I fill in "1-1 RECRUITMENT LIMITED" for "supplier_q"
  When I click the button "Search suppliers"
  Then I should be on "/suppliers/search?q=1-1+RECRUITMENT+LIMITED"

Scenario: Follows one of the latest news
  Given I am on "/"
  When  click the link "Customer newsletters for October"
  Then I should be on "/news/customer-newsletters-for-october-2"

Scenario: Follows browse all news articles link
  Given I am on "/"
  When I click the link "Browse all news articles"
  Then I should be on "/news/?&page=1"

Scenario: Follows browse all help and support guides link
  Given I am on "/"
  When I click the link "Browse all help and support guides"
  Then I should be on "buy-and-supply/"


Scenario: Follows links in the apollo-island
  Given I am on "/"
  When I click the link "collection[0].children[2].children[0]" in "govuk-grid-column-one-quarter"
  Then I should be on "/buy-and-supply/how-to-buy/"

  Given I am on "/"
  When I click the link "collection[1].children[2].children[0]" in "govuk-grid-column-one-quarter"
  Then I should be on "/buy-and-supply/agreements/"

  Given I am on "/"
  When I click the link "collection[2].children[2].children[0]" in "govuk-grid-column-one-quarter"
  Then I should be on "/agreements/upcoming"

  Given I am on "/"
  When I click the link "collection[3].children[1].children[0]" in "govuk-grid-column-one-quarter"
  Then I should be on "/buy-and-supply/purchasing-platform/"

  Given I am on "/"
  When I click the link "collection[3].children[3].children[0]" in "govuk-grid-column-one-quarter"
  Then I should be on "buy-and-supply/digital-marketplace/"

  Given I am on "/"
  When I click the link "collection[3].children[5].children[0]" in "govuk-grid-column-one-quarter"
  Then I should be on "/buy-and-supply/emarketplace/"