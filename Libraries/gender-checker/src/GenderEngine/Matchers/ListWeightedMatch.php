<?php

namespace GenderEngine\Matchers;

use GenderEngine\Gender;
use GenderEngine\Matchers\Interfaces\Matcher;
use GenderEngine\Matchers\Traits\NameList;

class ListWeightedMatch implements Matcher
{
    use NameList;

    public function test($name)
    {
        $maleFound = isset($this->maleList[$name]) ?
            $this->maleList[$name] : 0;
        $femaleFound = isset($this->femaleList[$name]) ?
            $this->femaleList[$name] : 0;

        if ($maleFound > $femaleFound) {
            return Gender::MALE;
        } elseif ($maleFound < $femaleFound) {
            return Gender::FEMALE;
        } else {
            return Gender::UNKNOWN;
        }
    }
}

