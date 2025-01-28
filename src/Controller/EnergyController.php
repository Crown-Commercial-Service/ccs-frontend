<?php

declare(strict_types=1);

namespace App\Controller;

use App\Utils\EnergySolutionToolQuestions;
use App\Utils\EnergySolutionToolDecision;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\HttpFoundation\Request;

class EnergyController extends AbstractController
{
    private $currentQuestion;
    private $currentQuestionHint;
    private $currentAnswer;

    public function start(Request $request)
    {
        $error      = false;
        $history    = $this->getHistory($request);

        if ($request->isMethod('POST')) {
            $userAnswer = $request->request-> get('answer', null);
            if ($userAnswer == null) {
                $error = true;
            } else {
                $history = $this->setHistory($history, $userAnswer);
                return $this->processToNextStage($history);
            }
        }

        return $this->render('energy/question.html.twig', [
            'question'         => $this->currentQuestion,
            'questionHint'     => $this->currentQuestionHint,
            'error'            => $error,
            'history'          => json_encode($history),
            'back'             => json_encode($this->prepareBackLink($history)),
            'selectedIndex'    => !is_null($history[array_key_last($history)]["selectedAnswer"]) ? (int) $history[array_key_last($history)]["selectedAnswer"] : null,
            'options'          => $this->currentAnswer,
        ]);
    }

    public function resultPage(Request $request)
    {
        $history = $request->query->get('history');
        $historyArray = $this->prepareHistoryArray($history);

        $recommendation    = $request->query->get('recommendation');

        return $this->render('energy/result.html.twig', [
            'history'          => $historyArray,
            'back'             => json_encode($history),
            'recommendation'   => $recommendation
        ]);
    }

    private function getHistory($request)
    {
        $history = null;

        if (isset($_REQUEST["history"])) {
            $history =  json_decode((string) $_REQUEST["history"], true);
            $this->setCurrentQuestionAndAnswers((int) $this->getCurrentQuesitonID($history));
        } else {
            $this->setCurrentQuestionAndAnswers(0);
            $history = [
                [
                    'questionID'    => '0',
                    'selectedAnswer' => null
                ],
            ];
        }

        return $history;
    }

    private function getCurrentQuesitonID($history)
    {

        $lastIndex = count($history) - 1;
        return $history[$lastIndex]['questionID'];
    }

    private function setHistory($history, $userSelectedOption)
    {

        $lastIndex = count($history) - 1;
        $history[$lastIndex]['selectedAnswer'] = $userSelectedOption;

        return $history;
    }

    private function setCurrentQuestionAndAnswers(int $id)
    {
        $result = EnergySolutionToolQuestions::getQuestionAndAnswers($id);

        $this->currentQuestion      = $result['question'];
        $this->currentQuestionHint  = $result['hint'] ?? null;
        $this->currentAnswer        = $result['answer'];
    }

    private function processToNextStage($history)
    {

        $lastEntry = $history[array_key_last($history)];

        $selectedAnswer = (int) $lastEntry['selectedAnswer'];

        $decision = EnergySolutionToolDecision::getDecision((int) $lastEntry['questionID']);
        $nextStage = $decision[$selectedAnswer];

        if (!is_string($nextStage)) {
            $history = $this->addQuestionToHistory($history, $nextStage);
            return $this->redirectToRoute('energy_question', ['history' => json_encode($history)]);
        } else {
            return $this->redirectToRoute('energy_result', ['history' => $history, 'recommendation' => $nextStage]);
        };
    }

    private function addQuestionToHistory($history, int $questionID)
    {
        $newQestionArray = (object) [
            'questionID'    => $questionID,
            'selectedAnswer' => null
        ];

        $history[] = $newQestionArray;
        return $history;
    }

    private function prepareBackLink($history)
    {
        $lastIndex = count($history) <= 0 ? 0 : count($history) - 1;
        unset($history[$lastIndex]);

        return $history;
    }

    private function prepareHistoryArray($history)
    {
        $historyArray = [];

        foreach ($history as $index => $eachSelection) {
            $qAndA = EnergySolutionToolQuestions::getQuestionAndSingleAnswer((int) $eachSelection["questionID"], (int) $eachSelection["selectedAnswer"]);
            $qAndA['history'] = json_encode(array_slice($history, 0, $index + 1));

            $historyArray[] = $qAndA;
        }


        return $historyArray;
    }
}
