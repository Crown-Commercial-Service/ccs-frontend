<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

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
    * @When I click the link :linkLocation in :linkclass 
    */
    public function iClickTheLinkIn($linkLocation, $linkclass)
    {
        $js = <<<JS
            const collection = document.getElementsByClassName("{$linkclass}");
            $linkLocation.click()
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

        $pageNumber -= 1;

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

    /**
     * @Then the URL should contain :url
     */
    public function theUrlShouldContain($url)
    {
        $currentURL = $this->getSession()->getCurrentUrl();

        if (!str_contains((string) $currentURL, (string) $url)){
            throw new Exception("URL ({$currentURL}) does not contain {$url}");
        }
    }

    /**
    * @When the page loaded
    */
    public function thePageLoaded()
    {
        sleep(2);
    }

    /**
    * Usage $this->saveHTML();
    */
    private function saveHTML (){
        $html = $this->getSession()->getPage()->getHtml();
        file_put_contents('features/bootstrap/HTML_output.txt', $html);
    }
}