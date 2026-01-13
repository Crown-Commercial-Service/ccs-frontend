<?php

namespace App\Controller\GuideMatch;

use App\Service\GuideJourneyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Strata\Frontend\Cms\RestData;
use Strata\Frontend\ContentModel\ContentModel;
use Strata\Frontend\Exception\NotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Exception;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Psr16Cache;

/**
 * Base controller for guide match journeys.
 *
 * Provides common functionality for handling guide match journeys, including
 * displaying questions, processing answers, managing session history, and
 * displaying results.
 */
abstract class BaseJourneyController extends AbstractController
{
    /** @var string The name of the current journey. Must be defined in child classes. */
    protected string $journeyName;

    /**
     * @param GuideJourneyService    $journeyService The service for managing guide journey data.
     * @param CacheItemPoolInterface $cache          The cache item pool for caching API responses.
     * @throws \Exception If $journeyName is not defined in the child class.
     */
    public function __construct(
        protected GuideJourneyService $journeyService,
        private CacheItemPoolInterface $cache
    ) {
        if (empty($this->journeyName)) {
            throw new \Exception('$journeyName must be defined in ' . static::class);
        }
    }

    /**
     * Returns the specific landing page data for the journey.
     *
     * @return array An associative array containing 'title' and 'description'.
     */
    abstract protected function getLandingPageData(): array;

    /**
     * Displays a question node or redirects based on the journey state.
     *
     * @param Request     $request      The current HTTP request.
     * @param string|null $questionUUID The UUID of the current question node, or null for the start node.
     * @return Response
     */
   public function show(Request $request, ?string $questionUUID = null): Response
    {
        $data = $this->journeyService->getJourneyData($this->journeyName);
        $uuid = $questionUUID ?? $data['start_uuid'];

        // 1. Check if it's a POST request (Submission)
        if ($request->isMethod('POST')) {
            return $this->handlePost($request, $uuid, $data);
        }

        // 2. Locate the content (Node or Outcome)
        $content = $this->journeyService->findNodeOrOutcome($uuid, $data);
        
        if (!$content) {
            return $this->redirectToRoute($this->journeyName . '_landing');
        }

        // 3. Handle Outcomes separately
        if ($content['type'] === 'outcome') {
            return $this->redirectToRoute($this->journeyName . '_result');
        }

        // 4. Handle Question Nodes (History + Render)
        $this->trackHistory($uuid, $request);

        // Get the saved answer for this specific node from the session
        $answers = $request->getSession()->get('journey_answers', []);
        $savedAnswer = $answers[$uuid] ?? null;

        return $this->render('guide_match/question.html.twig', [
            'node'         => $content['data'],
            'back_url'     => $this->getBackUrl($request),
            'form_url'     => $this->generateUrl($this->journeyName . '_journey', ['questionUUID' => $uuid]),
            'journey'      => $this->journeyName,
            'page'         => ['title' => ($this->hasError($request) ? 'Error: ' : '') . $content['data']['text']],
            'saved_answer' => $savedAnswer,
        ]);
    }

    /**
     * Handles POST requests for journey submissions, validating answers and determining the next step.
     *
     * @param Request $request     The current HTTP request.
     * @param string  $currentUuid The UUID of the current question node.
     * @param array   $data        The complete journey data.
     * @return Response
     * Separated POST logic to handle redirects and validation
     */
    protected function handlePost(Request $request, string $currentUuid, array $data): Response
    {
        $selectedAnswerId = $request->request->get('id');

        // Validation: If nothing is selected
        if (!$selectedAnswerId) {
            // Get the specific message for this node, or a generic fallback and store the message in the session flash bag
            $this->addFlash('error', $data['nodes'][$currentUuid]['validation_message'] ?? $data['default_validation_message']);
            
            return $this->redirectToRoute($this->journeyName . '_journey', ['questionUUID' => $currentUuid]);
        }

        // Save the answer to the session before moving on
        $session = $request->getSession();
        $answers = $session->get('journey_answers', []);
        $answers[$currentUuid] = $selectedAnswerId;
        $session->set('journey_answers', $answers);

        // Check if the selected answer is an OUTCOME
        $content = $this->journeyService->findNodeOrOutcome($selectedAnswerId, $data);
        
        if ($content && $content['type'] === 'outcome') {
            // MANUALLY push the outcome to history so the result page can find it
            $history = $session->get('journey_history', []);
            $history[] = $selectedAnswerId;
            $session->set('journey_history', $history);
            
            return $this->redirectToRoute($this->journeyName . '_result');
        }

        // Otherwise, continue to the next question
        return $this->redirectToRoute($this->journeyName . '_journey', ['questionUUID' => $selectedAnswerId]);
    }

    /**
     * Displays the landing page for the journey, clearing any previous session data.
     *
     * @return Response
     */
    public function landingPage(): Response
    {
        $request = $this->container->get('request_stack')->getCurrentRequest();
        $session = $request->getSession();
        
        // Clear both history and answers
        $session->remove('journey_history');
        $session->remove('journey_answers');

        $data = $this->journeyService->getJourneyData($this->journeyName);
        $startNodeId = $data['start_uuid'];
        
        return $this->render('guide_match/landing.html.twig', array_merge([
            'start_url'  => $this->generateUrl($this->journeyName . '_journey', ['questionUUID' => $startNodeId]),
        ], $this->getLandingPageData()));
    }

    /**
     * Displays the result page for the journey, showing a summary of answers and relevant agreements.
     *
     * @param Request $request The current HTTP request.
     * @return Response
     */
    public function resultPage(Request $request): Response
    {
        $data = $this->journeyService->getJourneyData($this->journeyName);
        $session = $request->getSession();
        $history = $session->get('journey_history', []);
        
        $answers = $session->get('journey_answers');

        // If session is empty, redirect to start
        if (!$answers) {
            $this->addFlash('warning', 'Your session has expired. Please start the journey again.');
            
            return $this->redirectToRoute($this->journeyName . '_landing');
        }
        
        // The last thing in our history is the outcome UUID
        $outcomeUuid = end($history);
        $content = $this->journeyService->findNodeOrOutcome($outcomeUuid, $data);

        if (!$content || $content['type'] !== 'outcome') {
            return $this->redirectToRoute($this->journeyName . '_landing');
        }

        $agreements = [];
        foreach ($content['data'] as $agreementData) {
            if ($agreementData['is_external_link'] ?? false) {
                $agreements[] = $agreementData;
                continue;
            }

            $agreements[] = $this->getAgreement($agreementData['agreement_id']);
        }

        return $this->render('guide_match/outcome.html.twig', [
            'outcome' => $content['data'],
            'summary' => $this->getOutcomeAnswers($request, $data),
            'page'    => ['title' => 'Your Results'],
            'start_url'  => $this->generateUrl($this->journeyName . '_journey', ['questionUUID' => $data['start_uuid']]),
            'agreements' => $agreements,
        ]);
    }

    /**
     * Updates the session history stack to track user progress.
     *
     * @param string  $uuid    The UUID of the current node.
     * @param Request $request The current HTTP request.
     * @return void
     */
    private function trackHistory(string $uuid, Request $request): void
    {
        $session = $request->getSession();
        $history = $session->get('journey_history', []);

        // If it's the start node, reset history to fresh state
        $data = $this->journeyService->getJourneyData($this->journeyName);
        if ($uuid === $data['start_uuid']) {
            $history = [$uuid];
        } else {
            // If moving backwards, trim the stack; otherwise, push the new node
            $key = array_search($uuid, $history);
            if ($key !== false) {
                $history = array_slice($history, 0, $key + 1);
            } else {
                $history[] = $uuid;
            }
        }
        
        $session->set('journey_history', $history);
    }

    /**
     * Retrieves a summary of the user's answers for the outcome page.
     *
     * @param Request $request The current HTTP request.
     * @param array   $data    The complete journey data.
     * @return array A list of questions, selected answers, and URLs to revisit questions.
     */
    private function getOutcomeAnswers(Request $request, array $data): array 
    {
        $session = $request->getSession();
        $savedAnswers = $session->get('journey_answers', []);
        $history = $session->get('journey_history', []);
        
        $summary = [];
        foreach ($history as $questionUuid) {
            if (isset($data['nodes'][$questionUuid]) && isset($savedAnswers[$questionUuid])) {
                $node = $data['nodes'][$questionUuid];
                $selectedId = $savedAnswers[$questionUuid];
                
                $label = 'Unknown';
                foreach ($node['answers'] as $answer) {
                    $val = $answer['next'] ?? $answer['outcome_id'];
                    if ($val === $selectedId) {
                        $label = $answer['label'];
                        break;
                    }
                }

                $summary[] = [
                    'question' => $node['text'],
                    'answer'   => $label,
                    'url'      => $this->generateUrl($this->journeyName . '_journey', ['questionUUID' => $questionUuid])
                ];
            }
        }

        return $summary;
    }

    /**
     * Returns the URL for the previous node or the landing page.
     *
     * @param Request $request The current HTTP request.
     * @return string The URL for the previous page.
     */
    private function getBackUrl(Request $request): string
    {
        $history = $request->getSession()->get('journey_history', []);
        $count = count($history);

        // If more than one node in history, go back to the previous UUID
        if ($count > 1) {
            return $this->generateUrl($this->journeyName . '_journey', [
                'questionUUID' => $history[$count - 2]
            ]);
        }

        // Default fallback to the landing page
        return $this->generateUrl($this->journeyName . '_landing');
    }

    /**
     * Checks if there are any error flash messages in the session.
     *
     * @param Request $request The current HTTP request.
     * @return bool True if an error message exists, false otherwise.
     */
    private function hasError(Request $request): bool
    {
        return $request->getSession()->getFlashBag()->has('error');
    }

    /**
     * Fetches agreement data from an external API based on the RM number.
     * @param string $rmNumber The RM number of the agreement to fetch.
     * @return array The agreement data.
     * @throws NotFoundHttpException If the framework agreement is not found.
     */
    protected function getAgreement(string $rmNumber): array {
        $api = new RestData(
            getenv('APP_API_BASE_URL'),
            new ContentModel(__DIR__ . '/../../../config/content/content-model.yaml')
        );
        $api->setContentType('frameworks');
        $psr16Cache = new Psr16Cache($this->cache);
        $api->setCache($psr16Cache);
        $api->setCacheLifetime(900);

        try {
            $results = $api->getOne($rmNumber);
            $content = $results->getContent(); // Strata\Frontend\Content\Content object
            $requiredData = [
                'title',
                'summary',
                'end_date',
                'rm_number',
                'regulation_type',
            ];
            $agreement = [];
            foreach ($content as $name => $field) {
                if (in_array($name, $requiredData)) {
                    $agreement[$name] = (string) $field;
                }
            }
        } catch (NotFoundException $e) {
            throw new NotFoundHttpException('Framework agreement not found', $e);
        }

        return $agreement;
    }
}