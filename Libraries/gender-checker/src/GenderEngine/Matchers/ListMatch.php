<?php

namespace GenderEngine\Matchers;

use GenderEngine\Gender;
use GenderEngine\Matchers\Interfaces\Matcher;
use GenderEngine\Matchers\Traits\NameList;

class ListMatch implements Matcher
{
    use NameList;

    public function test($name)
    {
        $maleFound   = isset($this->maleList[$name]);
        $femaleFound = isset($this->femaleList[$name]);

        if ($maleFound && ! $femaleFound) {
            return Gender::MALE;
        } elseif ( ! $maleFound && $femaleFound) {
            return Gender::FEMALE;
        } else {
            return Gender::UNKNOWN;
        }
    }
}

