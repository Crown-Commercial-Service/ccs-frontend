<?php

declare(strict_types=1);

namespace App\Tests\App\Helper;

use App\Helper\ControllerHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ControllerHelperTest extends TestCase
{
    // --- toSlug() ---

    public function testToSlugNormalisesString(): void
    {
        $this->assertSame('hello-world-', ControllerHelper::toSlug('Hello World!'));
    }

    public function testToSlugLowercasesMixedCaseString(): void
    {
        $this->assertSame('financial-services', ControllerHelper::toSlug('Financial Services'));
    }

    // --- toSlugList() ---

    public function testToSlugListJoinsAndPrefixesEntries(): void
    {
        $this->assertSame(
            'events/health|events/education-training',
            ControllerHelper::toSlugList(['Health', 'Education & Training'], 'events/')
        );
    }

    public function testToSlugListWithoutPrefixJoinsEntries(): void
    {
        $this->assertSame('health|education', ControllerHelper::toSlugList(['Health', 'Education']));
    }

    public function testToSlugListWithEmptyArrayReturnsEmptyString(): void
    {
        $this->assertSame('', ControllerHelper::toSlugList([]));
    }

    // --- honeyPot() ---

    public function testHoneyPotThrowsWhenFieldIsFilled(): void
    {
        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Bots are not allowed.');

        ControllerHelper::honeyPot('a bot filled this in');
    }

    public function testHoneyPotDoesNothingWhenFieldIsEmpty(): void
    {
        ControllerHelper::honeyPot('');
        ControllerHelper::honeyPot(null);

        $this->addToAssertionCount(2);
    }

    // --- getFormData() ---

    public function testGetFormDataExtractsExpectedFields(): void
    {
        $params = new ParameterBag([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'company' => 'Acme',
            'jobTitle' => 'Dev',
        ]);

        $this->assertSame([
            'name' => 'Jane',
            'email' => 'jane@example.com',
            'company' => 'Acme',
            'jobTitle' => 'Dev',
        ], ControllerHelper::getFormData($params));
    }

    public function testGetFormDataDefaultsMissingFieldsToNull(): void
    {
        $params = new ParameterBag(['name' => 'Jane']);

        $this->assertSame([
            'name' => 'Jane',
            'email' => null,
            'company' => null,
            'jobTitle' => null,
        ], ControllerHelper::getFormData($params));
    }

    // --- converArrayToStringForWordpress() ---

    public function testConverArrayToStringForWordpressJoinsPartialSelection(): void
    {
        $this->assertSame('1,2', ControllerHelper::converArrayToStringForWordpress(['1', '2'], 3));
    }

    public function testConverArrayToStringForWordpressReturnsNullWhenAllSelected(): void
    {
        $this->assertNull(ControllerHelper::converArrayToStringForWordpress(['1', '2'], 2));
    }

    public function testConverArrayToStringForWordpressReturnsNullWhenSelectionIsNull(): void
    {
        $this->assertNull(ControllerHelper::converArrayToStringForWordpress(null, 3));
    }

    public function testConverArrayToStringForWordpressPassesThroughStringInput(): void
    {
        $this->assertSame('already-a-string', ControllerHelper::converArrayToStringForWordpress('already-a-string', 3));
    }

    // --- extractRmNumberFromReferrer() ---

    public function testExtractRmNumberFromReferrerParsesValidReferrer(): void
    {
        $this->assertSame('RM1234', ControllerHelper::extractRmNumberFromReferrer('https://example.com/agreements/RM1234'));
    }

    public function testExtractRmNumberFromReferrerParsesReferrerWithLotSuffix(): void
    {
        $this->assertSame('RM1234.5', ControllerHelper::extractRmNumberFromReferrer('https://example.com/agreements/RM1234.5'));
    }

    public function testExtractRmNumberFromReferrerReturnsNullForEmptyReferrer(): void
    {
        $this->assertNull(ControllerHelper::extractRmNumberFromReferrer(''));
        $this->assertNull(ControllerHelper::extractRmNumberFromReferrer(null));
    }

    public function testExtractRmNumberFromReferrerReturnsNullForInvalidReferrer(): void
    {
        $this->assertNull(ControllerHelper::extractRmNumberFromReferrer('https://example.com/agreements/notanumber'));
    }

    // --- removeFromArray() ---

    public function testRemoveFromArrayFiltersMatchingValues(): void
    {
        $result = ControllerHelper::removeFromArray(['a', 'b', 'c'], ['b']);

        $this->assertSame([0 => 'a', 2 => 'c'], $result);
    }

    public function testRemoveFromArrayReturnsSameArrayWhenNoValuesMatch(): void
    {
        $result = ControllerHelper::removeFromArray(['a', 'b'], ['z']);

        $this->assertSame([0 => 'a', 1 => 'b'], $result);
    }

    // --- getArrayFromStringForParam() ---

    public function testGetArrayFromStringForParamExplodesCommaSeparatedString(): void
    {
        $request = Request::create('/x', 'GET', ['sector' => '1,2,3']);

        $this->assertSame(['1', '2', '3'], ControllerHelper::getArrayFromStringForParam($request, 'sector'));
    }

    public function testGetArrayFromStringForParamReturnsEmptyArrayWhenAllSelectedFlagSet(): void
    {
        $request = Request::create('/x', 'GET', ['allSector' => '1', 'sector' => '1,2']);

        $this->assertSame([], ControllerHelper::getArrayFromStringForParam($request, 'sector', 'allSector'));
    }

    public function testGetArrayFromStringForParamReturnsEmptyArrayWhenParamMissing(): void
    {
        $request = Request::create('/x', 'GET');

        $this->assertSame([], ControllerHelper::getArrayFromStringForParam($request, 'sector'));
    }

    public function testGetArrayFromStringForParamMapsPlusToSpaceForArrayInput(): void
    {
        $request = Request::create('/x', 'GET', ['sector' => ['1+A', '2+B']]);

        $this->assertSame(['1 A', '2 B'], ControllerHelper::getArrayFromStringForParam($request, 'sector'));
    }

    // --- validateCategory() ---

    public function testValidateCategoryReturnsExplodedStringWhenParamIsNotArray(): void
    {
        $request = Request::create('/x', 'GET', ['category' => 'Construction,Energy']);

        $this->assertSame(
            [['Construction', 'Energy'], ['Estates']],
            ControllerHelper::validateCategory($request, ['Estates'], 'category')
        );
    }

    public function testValidateCategoryReturnsEmptySelectionWhenParamMissing(): void
    {
        $request = Request::create('/x', 'GET');

        $this->assertSame(
            [[], ['Estates']],
            ControllerHelper::validateCategory($request, ['Estates'], 'category')
        );
    }

    public function testValidateCategoryAddsPillarWhenAllCategoriesSelected(): void
    {
        $request = Request::create('/x', 'GET', [
            'category' => ['Construction', 'Energy', 'Facilities Management'],
        ]);

        [$selected, $pillarArray] = ControllerHelper::validateCategory($request, [], 'category');

        $this->assertSame(['Construction', 'Energy', 'Facilities Management'], $selected);
        $this->assertContains('Estates', $pillarArray);
    }

    public function testValidateCategoryRemovesPillarWhenNotAllCategoriesSelected(): void
    {
        $request = Request::create('/x', 'GET', ['category' => ['Construction']]);

        [$selected, $pillarArray] = ControllerHelper::validateCategory($request, ['Estates'], 'category');

        $this->assertSame(['Construction'], $selected);
        $this->assertNotContains('Estates', $pillarArray);
    }
}
