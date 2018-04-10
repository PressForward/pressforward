<?php

namespace GenderEngine\Matchers;

use GenderEngine\Gender;
use GenderEngine\Matchers\Interfaces\Matcher;

class RegExpV2Match implements Matcher
{
    private static $nameRegExp = [
        'th?o(m|b)'   => Gender::MALE,
        'frank'       => Gender::MALE,
        'bil'         => Gender::MALE,
        'hans'        => Gender::MALE,
        'ron'         => Gender::MALE,
        'ro(z|s)'     => Gender::FEMALE,
        'walt'        => Gender::MALE,
        'krishna'     => Gender::MALE,
        'tri(c|sh)'   => Gender::FEMALE,
        'pas(c|qu)al' => Gender::MALE,
        'ellie'       => Gender::FEMALE,
        'anfernee'    => Gender::MALE
    ];

    public function test($name)
    {
        foreach (self::$nameRegExp as $regExp => $gender) {
            if (preg_match('/^' . $regExp . '/', $name)) {
                return $gender;
            }
        }

        return Gender::UNKNOWN;
    }
}

