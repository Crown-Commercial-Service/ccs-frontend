<?php

declare(strict_types=1);

namespace App\Tests\App\Controller;

use App\Controller\FormController;
use App\Tests\App\Mock\CMSContentMockFactory;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Exception\TransportException;

class FormControllerTest extends AbstractControllerTestCase
{
    private function getFormController(): FormController
    {
        return static::getContainer()->get(FormController::class);
    }

    private function injectApiMock(FormController $controller, $mockApi): void
    {
        $reflection = new \ReflectionClass($controller);
        $apiProperty = $reflection->getProperty('api');
        $apiProperty->setAccessible(true);
        $apiProperty->setValue($controller, $mockApi);
    }

    private function mockEsourcingDatesApi(array $buyerDates = [], array $supplierDates = [])
    {
        $mockContent = CMSContentMockFactory::createMockContent([
            'buyer_dates' => $buyerDates,
            'supplier_dates' => $supplierDates,
        ]);

        $mockPage = $this->createMock(\Strata\Frontend\Content\Page::class);
        $mockPage->method('getContent')->willReturn($mockContent);

        $mockApi = $this->createMock(\Strata\Frontend\Cms\RestData::class);
        $mockApi->method('getOne')->willReturn($mockPage);

        return $mockApi;
    }

    // --- validateContactCCS ---

    public function testValidateContactCcsReturnsFalseForValidQuestionData(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateContactCCS([
            'enquiryType'  => 'Website - Question',
            'name'         => 'Jane Doe',
            'email'        => 'jane@example.com',
            'phone'        => null,
            'company'      => 'Acme Ltd',
            'jobTitle'     => 'Buyer',
            'moreDetail'   => 'I have a question about an agreement',
            'callback'     => null,
            'customerType' => null,
            'contactWay'   => null,
        ]);

        $this->assertFalse($result);
    }

    public function testValidateContactCcsReturnsErrorsForMissingRequiredFields(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateContactCCS([
            'enquiryType'  => 'Website - Question',
            'name'         => null,
            'email'        => 'not-an-email',
            'phone'        => null,
            'company'      => null,
            'jobTitle'     => null,
            'moreDetail'   => null,
            'callback'     => null,
            'customerType' => null,
            'contactWay'   => null,
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['nameErr']['errors']);
        $this->assertNotEmpty($result['emailErr']['errors']);
        $this->assertNotEmpty($result['companyErr']['errors']);
        $this->assertNotEmpty($result['jobTitleErr']['errors']);
        $this->assertNotEmpty($result['moreDetailErr']['errors']);
    }

    public function testValidateContactCcsRequiresPhoneCustomerTypeAndContactWayForComplaintEnquiry(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateContactCCS([
            'enquiryType'  => 'Website - Complaint',
            'name'         => 'Jane Doe',
            'email'        => 'jane@example.com',
            'phone'        => null,
            'company'      => 'Acme Ltd',
            'jobTitle'     => 'Buyer',
            'moreDetail'   => 'More detail about my complaint',
            'callback'     => null,
            'customerType' => null,
            'contactWay'   => null,
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['phoneErr']['errors']);
        $this->assertNotEmpty($result['customerTypeErr']['errors']);
        $this->assertNotEmpty($result['contactWayErr']['errors']);
    }

    public function testValidateContactCcsRequiresPhoneWhenCallbackRequested(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateContactCCS([
            'enquiryType'  => 'Website - Question',
            'name'         => 'Jane Doe',
            'email'        => 'jane@example.com',
            'phone'        => null,
            'company'      => 'Acme Ltd',
            'jobTitle'     => 'Buyer',
            'moreDetail'   => 'I have a question',
            'callback'     => 'Yes',
            'customerType' => null,
            'contactWay'   => null,
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['phoneErr']['errors']);
    }

    // --- validateEsourcingRegister ---

    public function testValidateEsourcingRegisterReturnsFalseForValidData(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateEsourcingRegister([
            'name'  => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->assertFalse($result);
    }

    public function testValidateEsourcingRegisterReturnsErrorsForInvalidData(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateEsourcingRegister([
            'name'  => '',
            'email' => 'not-an-email',
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['nameErr']['errors']);
        $this->assertNotEmpty($result['emailErr']['errors']);
    }

    // --- validateEsourcingTraining ---

    public function testValidateEsourcingTrainingReturnsFalseForValidBuyerData(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateEsourcingTraining([
            'customerType' => 'Buyer',
            'buyerDate'    => '9th Jul 2030 - 10:00am',
            'supplierDate' => null,
            'name'         => 'Jane Doe',
            'phone'        => '07700900000',
            'email'        => 'jane@example.com',
        ]);

        $this->assertFalse($result);
    }

    public function testValidateEsourcingTrainingReturnsErrorsWhenNoDateSelected(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateEsourcingTraining([
            'customerType' => 'Buyer',
            'buyerDate'    => null,
            'supplierDate' => null,
            'name'         => 'Jane Doe',
            'phone'        => '07700900000',
            'email'        => 'jane@example.com',
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['dateErr']['errors']);
    }

    public function testValidateEsourcingTrainingReturnsErrorForPastDate(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateEsourcingTraining([
            'customerType' => 'Supplier',
            'buyerDate'    => null,
            'supplierDate' => '9th Jul 2020 - 10:00am',
            'name'         => 'Jane Doe',
            'phone'        => '07700900000',
            'email'        => 'jane@example.com',
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['dateErr']['errors']);
    }

    public function testValidateEsourcingTrainingReturnsErrorsForMissingCustomerType(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateEsourcingTraining([
            'customerType' => null,
            'buyerDate'    => null,
            'supplierDate' => null,
            'name'         => 'Jane Doe',
            'phone'        => '07700900000',
            'email'        => 'jane@example.com',
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['customerTypeErr']['errors']);
    }

    // --- validateGatedForm ---

    public function testValidateGatedFormReturnsFalseForValidData(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateGatedForm([
            'name'     => 'Jane Doe',
            'jobTitle' => 'Buyer',
            'company'  => 'Acme Ltd',
            'email'    => 'jane@example.com',
        ]);

        $this->assertFalse($result);
    }

    public function testValidateGatedFormReturnsErrorsForInvalidData(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateGatedForm([
            'name'     => '',
            'jobTitle' => '',
            'company'  => '',
            'email'    => 'not-an-email',
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['nameErr']['errors']);
        $this->assertNotEmpty($result['jobTitleErr']['errors']);
        $this->assertNotEmpty($result['companyErr']['errors']);
        $this->assertNotEmpty($result['emailErr']['errors']);
    }

    // --- validateEventsForm ---

    public function testValidateEventsFormReturnsFalseForValidData(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateEventsForm([
            'name'     => 'Jane Doe',
            'jobTitle' => 'Buyer',
            'email'    => 'jane@example.com',
            'company'  => 'Acme Ltd',
            'phone'    => '07700900000',
        ]);

        $this->assertFalse($result);
    }

    public function testValidateEventsFormReturnsErrorsForInvalidData(): void
    {
        $controller = $this->getFormController();

        $result = $controller->validateEventsForm([
            'name'     => '',
            'jobTitle' => '',
            'email'    => 'not-an-email',
            'company'  => '',
            'phone'    => '',
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['nameErr']['errors']);
        $this->assertNotEmpty($result['jobTitleErr']['errors']);
        $this->assertNotEmpty($result['emailErr']['errors']);
        $this->assertNotEmpty($result['companyErr']['errors']);
        $this->assertNotEmpty($result['phoneErr']['errors']);
    }

    // --- validateNewsletterForm (private) ---

    public function testValidateNewsletterFormReturnsFalseForValidData(): void
    {
        $controller = $this->getFormController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateNewsletterForm');
        $method->setAccessible(true);

        $result = $method->invoke($controller, [
            'name'     => 'Jane Doe',
            'jobTitle' => 'Buyer',
            'company'  => 'Acme Ltd',
            'email'    => 'jane@example.com',
        ]);

        $this->assertFalse($result);
    }

    public function testValidateNewsletterFormReturnsErrorsForInvalidData(): void
    {
        $controller = $this->getFormController();

        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('validateNewsletterForm');
        $method->setAccessible(true);

        $result = $method->invoke($controller, [
            'name'     => '',
            'jobTitle' => '',
            'company'  => '',
            'email'    => 'not-an-email',
        ]);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result['nameErr']['errors']);
        $this->assertNotEmpty($result['emailErr']['errors']);
    }

    // --- /contact (GET view) ---

    public function testContactCcsViewRendersSuccessfully(): void
    {
        $this->client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Contact GCA', $html);
    }

    public function testContactCcsViewRendersSuccessfullyWithReferrer(): void
    {
        $this->client->request('GET', '/contact', [], [], [
            'HTTP_REFERER' => 'https://example.com/agreements/RM6187',
        ]);

        $this->assertResponseIsSuccessful();
    }

    // --- /complaint (GET view) ---

    public function testComplaintFormViewRendersSuccessfully(): void
    {
        $this->client->request('GET', '/complaint');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Complaint form', $html);
    }

    // --- /contact/submit ---

    public function testContactCcsSubmitRendersValidationErrorsForInvalidData(): void
    {
        $this->client->request('POST', '/contact/submit', [
            'origin'      => 'Website - Question',
            'name'        => '',
            'email'       => 'not-an-email',
            'company'     => '',
            'jobTitle'    => '',
            'more-detail' => '',
        ]);

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('There is a problem', $html);
        $this->assertStringContainsString('Enter your name', $html);
    }

    public function testContactCcsSubmitRedirectsToThanksPageOnValidData(): void
    {
        $this->client->request('POST', '/contact/submit', [
            'origin'      => 'Website - Question',
            'name'        => 'Jane Doe',
            'email'       => 'jane@example.com',
            'company'     => 'Acme Ltd',
            'jobTitle'    => 'Buyer',
            'more-detail' => 'I have a question about an agreement',
        ]);

        $this->assertResponseRedirects('/contact/thanks');
    }

    public function testContactCcsSubmitBlocksBotsViaHoneypot(): void
    {
        $this->client->request('POST', '/contact/submit', [
            'surname' => '1',
            'origin'  => 'Website - Question',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    // --- /complaint/submit ---

    public function testComplaintFormSubmitRendersValidationErrorsForInvalidData(): void
    {
        $this->client->request('POST', '/complaint/submit', [
            'origin'  => 'Website - Complaint',
            'name'    => '',
            'email'   => 'not-an-email',
            'company' => '',
        ]);

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('There is a problem', $html);
    }

    public function testComplaintFormSubmitRedirectsToThanksPageOnValidData(): void
    {
        $this->client->request('POST', '/complaint/submit', [
            'origin'       => 'Website - Complaint',
            'name'         => 'Jane Doe',
            'email'        => 'jane@example.com',
            'company'      => 'Acme Ltd',
            'jobTitle'     => 'Buyer',
            'phone'        => '07700900000',
            'customerType' => 'Buyer',
            'contactWay'   => 'Email',
            'more-detail'  => 'Details of my complaint',
        ]);

        $this->assertResponseRedirects('/contact/thanks-complaint');
    }

    // --- /newsletters/submit ---

    public function testNewslettersRendersValidationErrorsForInvalidData(): void
    {
        $this->client->request('POST', '/newsletters/submit', [
            'name'    => '',
            'email'   => 'not-an-email',
            'company' => '',
        ]);

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('There is a problem', $html);
    }

    public function testNewslettersRedirectsToThanksPageOnValidData(): void
    {
        $this->client->request('POST', '/newsletters/submit', [
            'name'     => 'Jane Doe',
            'email'    => 'jane@example.com',
            'company'  => 'Acme Ltd',
            'jobTitle' => 'Buyer',
        ]);

        $this->assertResponseRedirects('/newsletter/thanks');
    }

    // --- /esourcing-register/submit ---

    public function testEsourcingRegisterSubmitRendersValidationErrorsForInvalidData(): void
    {
        $this->client->request('POST', '/esourcing-register/submit', [
            'name'  => '',
            'email' => 'not-an-email',
        ]);

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('There is a problem', $html);
    }

    public function testEsourcingRegisterSubmitRedirectsToThanksPageOnValidData(): void
    {
        $this->client->request('POST', '/esourcing-register/submit', [
            'name'  => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $this->assertResponseRedirects('/esourcing-register/thanks');
    }

    // --- /esourcing-training & /esourcing-training/submit ---

    public function testEsourcingTrainingViewRendersSuccessfully(): void
    {
        $controller = $this->getFormController();
        $this->injectApiMock($controller, $this->mockEsourcingDatesApi());

        $this->client->request('GET', '/esourcing-training');

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Book online training for the GCA eSourcing tool', $html);
    }

    public function testEsourcingTrainingSubmitRendersValidationErrorsForInvalidData(): void
    {
        $controller = $this->getFormController();
        $this->injectApiMock($controller, $this->mockEsourcingDatesApi());

        $this->client->request('POST', '/esourcing-training/submit', [
            'customer-type' => null,
            'name'          => 'Jane Doe',
            'phone'         => '07700900000',
            'email'         => 'jane@example.com',
        ]);

        $this->assertResponseIsSuccessful();
        $html = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('There is a problem', $html);
    }

    public function testEsourcingTrainingSubmitRedirectsToThanksPageOnValidData(): void
    {
        $this->client->request('POST', '/esourcing-training/submit', [
            'customer-type'        => 'Buyer',
            'buyer-training-dates' => '9th Jul 2030 - 10:00am',
            'name'                 => 'Jane Doe',
            'phone'                => '07700900000',
            'email'                => 'jane@example.com',
        ]);

        $this->assertResponseRedirects('/esourcing-training/thanks');
    }

    // --- /csat/submit ---

    public function testSubmitCsatSurveyReturnsBadRequestForMissingRating(): void
    {
        $this->client->request(
            'POST',
            '/csat/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['feedback-comments' => 'Great service'])
        );

        $this->assertResponseStatusCodeSame(400);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertFalse($payload['success']);
    }

    public function testSubmitCsatSurveyReturnsBadRequestForNonNumericRating(): void
    {
        $this->client->request(
            'POST',
            '/csat/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['rating' => 'excellent'])
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testSubmitCsatSurveyReturnsServiceUnavailableWhenQualtricsConfigMissing(): void
    {
        // .env.test does not define QUALTRICS_API_TOKEN / QUALTRICS_SURVEY_ID, so this
        // hits the missing-config branch by default.
        $this->client->request(
            'POST',
            '/csat/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['rating' => 8])
        );

        $this->assertResponseStatusCodeSame(500);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertFalse($payload['success']);
    }

    private function injectQualtricsConfig(FormController $controller, string $token, string $surveyId): void
    {
        $reflection = new \ReflectionClass($controller);

        $tokenProperty = $reflection->getProperty('qualtricsApiToken');
        $tokenProperty->setAccessible(true);
        $tokenProperty->setValue($controller, $token);

        $surveyIdProperty = $reflection->getProperty('qualtricsSurveyId');
        $surveyIdProperty->setAccessible(true);
        $surveyIdProperty->setValue($controller, $surveyId);
    }

    public function testSubmitCsatSurveyReturnsSuccessWhenQualtricsAccepts(): void
    {
        $controller = $this->getFormController();
        $this->injectQualtricsConfig($controller, 'fake-token', 'SV_fake');

        $this->mockHttpClient->setResponseFactory(function () {
            return new MockResponse(json_encode(['result' => ['responseId' => 'R_fake123']]), ['http_code' => 200]);
        });

        $this->client->request(
            'POST',
            '/csat/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['rating' => 9, 'feedback-comments' => 'Great service'])
        );

        $this->assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertTrue($payload['success']);
    }

    public function testSubmitCsatSurveyReturnsBadRequestWhenQualtricsRejectsSubmission(): void
    {
        $controller = $this->getFormController();
        $this->injectQualtricsConfig($controller, 'fake-token', 'SV_fake');

        $this->mockHttpClient->setResponseFactory(function () {
            return new MockResponse(json_encode(['result' => []]), ['http_code' => 200]);
        });

        $this->client->request(
            'POST',
            '/csat/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['rating' => 9])
        );

        $this->assertResponseStatusCodeSame(400);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        $this->assertFalse($payload['success']);
    }

    public function testSubmitCsatSurveyReturnsServerErrorWhenRequestThrows(): void
    {
        $controller = $this->getFormController();
        $this->injectQualtricsConfig($controller, 'fake-token', 'SV_fake');

        $this->mockHttpClient->setResponseFactory(function () {
            throw new TransportException('Connection refused');
        });

        $this->client->request(
            'POST',
            '/csat/submit',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['rating' => 9])
        );

        $this->assertResponseStatusCodeSame(500);
    }
}
