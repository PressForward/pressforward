<?php

namespace GenderEngine;

use GenderEngine\Gender;
use GenderEngine\Exceptions as Exceptions;
use GenderEngine\Matchers\Interfaces\Matcher;

class GenderEngine
{
    private $defaultListFolder = null;

    private $maleList = [];

    private $femaleList = [];

    private $name = '';

    private $confidence = 0;

    private $previousMatchConfidence = -1;

    private $matcherConfidenceOrder = array(
        'ListMatch',
        'ListWeightedMatch',
        'MetaphoneMatch',
        'MetaphoneWeightedMatch',
        'RegExpV2Match',
        'RegExpV1Match',
//        'RestNamesWSMatch',
//        'BabyNamesWSMatch'
    );

    public function __construct(
        $confidence = null,
        $maleList = 'MaleNames.json',
        $femaleList = 'FemaleNames.json',
        $useListFolder = true
    ) {
        if ($useListFolder) {
            $this->defaultListFolder = __DIR__ . '/Lists/';
        }

        if ($confidence !== null && ! is_int($confidence)) {
            throw new \UnexpectedValueException('Invalid confidence level supplied, must a unsigned integer.');
        }

        $this->confidence = ($confidence === null) ?
            sizeof($this->matcherConfidenceOrder) : $confidence;

        if ( ! empty($maleList)) {
            $this->loadMaleList($this->defaultListFolder . $maleList);
        }

        if ( ! empty($femaleList)) {
            $this->loadFemaleList($this->defaultListFolder . $femaleList);
        }

        return $this;
    }

    public function loadMaleList($fileName)
    {
        $newMaleList = $this->loadJsonFileContents($fileName);

        if ($newMaleList !== null) {
            $this->maleList = $newMaleList;
        }

        return $this;
    }

    public function loadFemaleList($fileName)
    {
        $newFemaleList = $this->loadJsonFileContents($fileName);

        if ($newFemaleList !== null) {
            $this->femaleList = $newFemaleList;
        }

        return $this;
    }

    public function test($name, $confidence = null)
    {
        if ( ! is_string($name) || empty($name)) {
            throw new \UnexpectedValueException('The specified name is not a valid string.');
        }

        $genderGuess = Gender::UNKNOWN;
        $this->previousMatchConfidence = -1;

        if ( ! is_int($confidence)) {
            $confidence = $this->confidence;
        }

        for ($i = 0; $i < $confidence; $i++) {
            if ( ! isset($this->matcherConfidenceOrder[$i])) {
                break;
            }

            $matcherClassName = 'GenderEngine\Matchers\\' . $this->matcherConfidenceOrder[$i];
            $matcher = new $matcherClassName();

            if ($matcher instanceof Matcher) {
                if (method_exists($matcher, 'setNameLists')) {
                    $matcher->setNameLists($this->maleList, $this->femaleList);
                }

                $genderGuess = $matcher->test($name);

                if ($genderGuess !== Gender::UNKNOWN) {
                    $this->previousMatchConfidence = ++$i;
                    break;
                }
            } else {
                throw new Exceptions\InvalidMatcherException("'$matcherClassName' is not a valid matcher.");
            }
        }

        return $genderGuess;
    }

    public function getPreviousMatchConfidence()
    {
        return $this->previousMatchConfidence;
    }

    public function getConfidence()
    {
        return $this->confidence;
    }

    public function getMaleList()
    {
        return $this->maleList;
    }

    public function getFemaleList()
    {
        return $this->femaleList;
    }

    public function setMatcherConfidenceOrder(array $newMatcherConfidenceOrder)
    {
        $this->matcherConfidenceOrder = $newMatcherConfidenceOrder;
    }

    public function getMatcherConfidenceOrder()
    {
        return $this->matcherConfidenceOrder;
    }

    private function loadJsonFileContents($fileName)
    {
        if (file_exists($fileName)) {
            $jsonString = @file_get_contents($fileName);

            if ( ! empty($jsonString)) {
                $jsonDecode = @json_decode($jsonString, true);

                if ( ! empty($jsonDecode)) {
                    return $jsonDecode;
                } else {
                    throw new Exceptions\JSONDecodeException('The specifed file could not be successfully decoded.');
                }
            } else {
                throw new Exceptions\FileEmptyException('No contents could be retrieved from the specified file.');
            }
        } else {
            throw new \FileNotFoundException('The specified file could not be found.');
        }

        return null;
    }
}

