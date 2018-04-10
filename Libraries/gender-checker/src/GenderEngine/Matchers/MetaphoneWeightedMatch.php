<?php

namespace GenderEngine\Matchers;

use GenderEngine\Gender;
use GenderEngine\Matchers\Interfaces\Matcher;
use GenderEngine\Matchers\Traits\NameList;

class MetaphoneWeightedMatch implements Matcher
{
    use NameList;

    public function test($name)
    {
        $metaphoneFunction = (is_callable('double_metaphone')) ?
            'double_metaphone' : 'metaphone';

        $nameMetaphone = $metaphoneFunction($name);
        $maleFound     = 0;
        $femaleFound   = 0;

        foreach ($this->femaleList as $femaleName => $femaleWeight) {
            if ($nameMetaphone === $metaphoneFunction($femaleName)) {
                $femaleFound = $femaleWeight;
                break;
            }
        }

        foreach ($this->maleList as $maleName => $maleWeight) {
            if ($nameMetaphone === $metaphoneFunction($maleName)) {
                $maleFound = $maleWeight;
                break;
            }
        }

        if ($maleFound > $femaleFound) {
            return Gender::MALE;
        } elseif ($maleFound < $femaleFound) {
            return Gender::FEMALE;
        } else {
            return Gender::UNKNOWN;
        }
    }
}

