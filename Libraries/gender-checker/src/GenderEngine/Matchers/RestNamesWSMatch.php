<?php

namespace GenderEngine\Matchers;

use GenderEngine\Gender;
use GenderEngine\Matchers\Interfaces\Matcher;

class RestNamesWSMatch implements Matcher
{
    private $restUrl = "http://www.thomas-bayer.com/restnames/name.groovy";

    public function test($name)
    {
        $streamContext    = stream_context_create(['http' => ['timeout' => 5]]);
        $restResultString = file_get_contents($this->restUrl . '?name=' . $name, 0, $streamContext);

        if ( ! empty($restResultString)) {
            $restResultXml = simplexml_load_string($restResultString);

            if ($restResultXml !== false) {
                $maleHit = isset($restResultXml->nameinfo->male) ?
                    (string)$restResultXml->nameinfo->male : null;

                $femaleHit = isset($restResultXml->nameinfo->female) ?
                    (string)$restResultXml->nameinfo->female : null;

                if ($maleHit !== null && $femaleHit !== null) {
                    if ($maleHit === 'true' && $femaleHit === 'false') {
                        return Gender::MALE;
                    } elseif ($maleHit === 'false' && $femaleHit === 'true') {
                        return Gender::FEMALE;
                    }
                }
            }
        }

        return Gender::UNKNOWN;
    }
}

