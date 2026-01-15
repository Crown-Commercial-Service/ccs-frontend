<?php

namespace App\Tests\Controller\GuideMatch;

use App\Controller\GuideMatch\BaseJourneyController;
use App\Service\GuideJourneyService;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class BaseJourneyControllerTest extends TestCase
{
    private $journeyService;
    private $cache;
    private $container;
    private $controller;
    private $session;
    private $router;
    private $twig;
    private $requestStack;
    private $flashBag;
    private $request; // We keep a reference to the request used in tests
    private $sessionData = [];

    protected function setUp(): void
    {
        // 1. Create Mocks
        $this->journeyService = $this->createMock(GuideJourneyService::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->session = $this->createMock(Session::class); // Mock concrete Session
        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        $this->container = $this->createMock(ContainerInterface::class);

        // 2. Setup Container
        $this->container->method('has')->willReturn(true);
        $this->container->method('get')->willReturnCallback(function ($id) {
            return match ($id) {
                'router' => $this->router,
                'twig' => $this->twig,
                'request_stack' => $this->requestStack,
                default => null,
            };
        });

        // 3. Initialize Controller
        // 3. Initialize Controller (Using an Anonymous Class to fix PSR-1 error)
        $this->controller = new class ($this->journeyService, $this->cache) extends BaseJourneyController {
            protected string $journeyName = 'test_journey';

            protected function getLandingPageData(): array
            {
                return [
                    'title' => 'Test Journey Title',
                    'description' => 'Test Journey Description',
                ];
            }

            // Mock the API call so tests don't need real data
            protected function getAgreement(string $rmNumber): array
            {
                return [
                    'title' => 'Mock Agreement',
                    'summary' => 'Mock Summary',
                    'end_date' => '2025-12-31',
                    'rm_number' => $rmNumber,
                    'regulation_type' => 'Mock Type',
                ];
            }
        };

        $this->controller->setContainer($this->container);

        // 4. FIX: Setup Request and bind Session
        // We create one request object and ensure the stack returns IT, not a new one.
        $this->request = new Request();
        $this->request->setSession($this->session);

        $this->requestStack->method('getSession')->willReturn($this->session);
        $this->requestStack->method('getCurrentRequest')->willReturn($this->request);
        $this->requestStack->method('getMainRequest')->willReturn($this->request);

        // 5. FIX: Configure FlashBag to prevent TypeError
        $this->session->method('getFlashBag')->willReturn($this->flashBag);
        $this->flashBag->method('has')->willReturn(false); // Default: no errors

        // 6. Mock Session Storage Logic
        $this->sessionData = [];
        $this->session->method('get')->willReturnCallback(function ($key, $default = null) {
            return array_key_exists($key, $this->sessionData) ? $this->sessionData[$key] : $default;
        });
        $this->session->method('set')->willReturnCallback(function ($key, $value) {
            $this->sessionData[$key] = $value;
        });
        $this->session->method('remove')->willReturnCallback(function ($key) {
            unset($this->sessionData[$key]);
        });
    }

    public function testConstructorThrowsExceptionIfJourneyNameMissing()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('$journeyName must be defined');

        new class ($this->journeyService, $this->cache) extends BaseJourneyController {
            protected string $journeyName = '';
            protected function getLandingPageData(): array
            {
                return [];
            }
        };
    }

    public function testLandingPageClearsSessionAndRenders()
    {
        // Setup initial "dirty" session data
        $this->sessionData = ['journey_history' => ['old'], 'journey_answers' => ['old']];

        $this->journeyService->method('getJourneyData')->willReturn(['start_uuid' => 'start-node']);

        // Expect render to be called
        $this->twig->expects($this->once())->method('render')->willReturn('rendered_content');
        // Expect router to generate start URL
        $this->router->method('generate')->willReturn('/start-url');

        $response = $this->controller->landingPage();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered_content', $response->getContent());

        // Assert session was cleared
        $this->assertArrayNotHasKey('journey_history', $this->sessionData);
        $this->assertArrayNotHasKey('journey_answers', $this->sessionData);
    }

    public function testShowDisplaysQuestionNode()
    {
        // We need to inject the URL parameter into our request object manually for this test
        // But since the controller just uses the method arg $questionUUID, strict request path isn't critical.

        $journeyData = [
            'start_uuid' => 'start-node',
            'nodes' => [
                'node-1' => ['text' => 'Question 1', 'validation_message' => 'Error', 'answers' => []]
            ]
        ];

        $this->journeyService->method('getJourneyData')->willReturn($journeyData);
        $this->journeyService->method('findNodeOrOutcome')->willReturn([
            'type' => 'node',
            'data' => $journeyData['nodes']['node-1']
        ]);

        $this->twig->method('render')->willReturn('question_page');
        $this->router->method('generate')->willReturn('/form-url');

        // Pass the SAME request object we configured in setUp
        $response = $this->controller->show($this->request, 'node-1');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('question_page', $response->getContent());
    }

    public function testShowHandlesPostValidationFailure()
    {
        // Update request to be POST
        $this->request->setMethod('POST');
        // No 'id' in request means validation failure

        $journeyData = [
            'nodes' => [
                'node-1' => ['validation_message' => 'Please select an option']
            ],
            'default_validation_message' => 'Required'
        ];
        $this->journeyService->method('getJourneyData')->willReturn($journeyData);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('error', 'Please select an option');

        // FIX: Ensure router returns the URL we expect in the assertion
        $this->router->method('generate')->willReturn('/redirect-url');

        $response = $this->controller->show($this->request, 'node-1');

        $this->assertTrue($response->isRedirect('/redirect-url'));
    }

    public function testShowHandlesPostValidAnswerAndRedirectsToNextNode()
    {
        // Update request to be POST and have ID
        $this->request->setMethod('POST');
        $this->request->request->set('id', 'node-2');

        $journeyData = ['nodes' => ['node-1' => []]];
        $this->journeyService->method('getJourneyData')->willReturn($journeyData);

        $this->journeyService->method('findNodeOrOutcome')->willReturn([
            'type' => 'node',
            'data' => []
        ]);

        // FIX: Ensure router returns the URL we expect
        $this->router->method('generate')->willReturn('/next-node-url');

        $response = $this->controller->show($this->request, 'node-1');

        $this->assertTrue($response->isRedirect('/next-node-url'));
        // Check session storage
        $this->assertEquals('node-2', $this->sessionData['journey_answers']['node-1']);
    }

    public function testResultPageRedirectsIfNoAnswers()
    {
        // Session is empty by default in setUp

        $this->flashBag->expects($this->once())
             ->method('add')
             ->with('warning', self::anything());

        // FIX: The controller redirects to {journey}_landing.
        // We assert the router produces '/landing' and the response redirects to it.
        $this->router->method('generate')->willReturn('/landing');

        $response = $this->controller->resultPage($this->request);

        $this->assertTrue($response->isRedirect('/landing'));
    }

    public function testResultPageRendersOutcome()
    {
        // Setup valid session state
        $this->sessionData['journey_answers'] = ['q1' => 'a1'];
        $this->sessionData['journey_history'] = ['outcome-1'];

        $journeyData = ['start_uuid' => 'start'];
        $this->journeyService->method('getJourneyData')->willReturn($journeyData);

        $this->journeyService->method('findNodeOrOutcome')->willReturn([
            'type' => 'outcome',
            'data' => [['agreement_id' => 'RM123']]
        ]);

        // FIX: We must specify return value here, as we removed the default in setUp
        $this->twig->method('render')->willReturn('outcome_page');
        $this->router->method('generate')->willReturn('/start-url'); // For the 'Start Again' link

        $response = $this->controller->resultPage($this->request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('outcome_page', $response->getContent());
    }
}
