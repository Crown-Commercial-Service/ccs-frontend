<?php

declare(strict_types=1);

namespace App\Utils;

use Symfony\Component\Yaml\Yaml;

class EnergySolutionToolQuestions
{
    protected static function loadConfig()
    {
        static $config;
        if (is_array($config)) {
            return $config;
        }

        $config = Yaml::parse(file_get_contents(__DIR__ . '/../../config/energy/energy_questions.yaml'));
        return $config;
    }

    public static function getQuestionAndAnswers(int $id): array
    {
        $config = self::loadConfig();

        return $config[$id] ?? [];
    }

    public static function getQuestionAndSingleAnswer(int $questionID, int $answerID): array
    {
        $config = self::loadConfig();

        $questionData = $config[$questionID];

        $question = $questionData["question"];
        $answer = array_keys($questionData["answer"])[$answerID];

        return ['question' => $question, 'answer' => $answer];
    }
}
