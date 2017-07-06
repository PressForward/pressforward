<?php

namespace GenderEngine\Test\Matchers;

use GenderEngine\Matchers\RegExpV2Match as RegExpV2Match;

class RegExpV2MatchTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->regExpMatcher = new RegExpV2Match();
    }

    public function tearDown()
    {
    }

    public function testInheritsMatcher()
    {
        $this->assertInstanceOf('GenderEngine\Matchers\Interfaces\Matcher', $this->regExpMatcher);
    }

    public function testFoundMaleNames()
    {
        $foundMaleNames = ['frank', 'pascal', 'hans'];

        foreach ($foundMaleNames as $maleName) {
            $this->assertEquals('MALE', $this->regExpMatcher->test($maleName));
        }
    }

    public function testFoundFemaleNames()
    {
        $foundFemaleNames = ['trish', 'roz', 'ellie'];

        foreach ($foundFemaleNames as $femaleName) {
            $this->assertEquals('FEMALE', $this->regExpMatcher->test($femaleName));
        }
    }

    public function testNotFoundName()
    {
        $this->assertEquals('UNKNOWN', $this->regExpMatcher->test('john'));
    }
}

