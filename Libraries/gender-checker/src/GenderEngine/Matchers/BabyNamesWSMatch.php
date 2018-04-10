<?php

namespace GenderEngine\Matchers;

use GenderEngine\Gender;
use GenderEngine\Matchers\Interfaces\Matcher;

class BabyNamesWSMatch implements Matcher
{
    private $restUrl = "http://www.gpeters.com/names/baby-names.php";

    public function test($name)
    {
        $streamContext    = stream_context_create(['http' => ['timeout' => 5]]);
        $restResultString = file_get_contents($this->restUrl . '?name=' . $name, 0, $streamContext);

        if ( ! empty($restResultString)) {
            $maleHit   = (strpos($restResultString, '<b>It\'s a boy!</b>')) > -1;
            $femaleHit = (strpos($restResultString, '<b>It\'s a girl!</b>')) > -1;

            if ($maleHit && ! $femaleHit) {
                return Gender::MALE;
            } elseif ( ! $maleHit && $femaleHit) {
                return Gender::FEMALE;
            }
        }

        return Gender::UNKNOWN;
    }
}

