<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
    * @When I click the link :link
    * @Given click the link :link
    */
    public function clickTheLink($link)
    {
        $js = <<<JS
            var xpath = "//a[contains(., '{$link}')]"
            var node = document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);

            node.singleNodeValue.click()
        JS;

        $this->getSession()->getDriver()->executeScript($js);
        $this->waitForThePageToBeLoaded();
    }

    /**
     * @When I click the button :link
     */
    public function iClickTheButton($link)
    {
        $js = <<<JS
            var xpath = "//button[contains(., '{$link}')]"
            var node = document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null);

            node.singleNodeValue.click()
        JS;

        $this->getSession()->getDriver()->executeScript($js);
        $this->waitForThePageToBeLoaded();
    }

    /**
     * @When I navigate to page :pageNumber
     */
    public function iNavigateToPage($pageNumber)
    {

        $pageNumber = $pageNumber -1;

        $js = <<<JS
            const collection = document.getElementsByClassName("pagination__item");
            collection[{$pageNumber}].children[0].click()
        JS;

        $this->getSession()->getDriver()->executeScript($js);
        $this->waitForThePageToBeLoaded();
    }

    /**
     * @Then /^wait for the page to be loaded$/
     */
    public function waitForThePageToBeLoaded()
    {
        $this->getSession()->wait(5000, "document.readyState === 'complete'");
    }


    /**
     * @Then I should see the following error messages:
     */
    public function iShouldSeeTheFollowingErrorMessages(TableNode $table)
    {
        foreach( $table->getRows() as $eachEntry){
            $errorMessage = $eachEntry[0];
            $this->assertSession()->elementTextContains('css', "div.govuk-error-summary.govuk-grid-column-three-quarters > div > ul", $errorMessage);
        }
        return true;
    }

    /**
     * @Then I should see :arg1 error messages
     */
    public function iShouldSeeErrorMessages($arg1)
    {
        $this->assertSession()->elementsCount('css', "div.govuk-error-summary.govuk-grid-column-three-quarters > div > ul > a", $arg1);
    }
}