<?php

namespace GenderEngine\Test\Matchers;

use GenderEngine\Matchers\RegExpV1Match as RegExpV1Match;

class RegExpV1MatchTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->regExpMatcher = new RegExpV1Match();
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
        $foundMaleNames = ['barry', 'eugene', 'vince'];

        foreach ($foundMaleNames as $maleName) {
            $this->assertEquals('MALE', $this->regExpMatcher->test($maleName));
        }
    }

    public function testFoundFemaleNames()
    {
        $foundFemaleNames = ['janet', 'karen', 'rachel'];

        foreach ($foundFemaleNames as $femaleName) {
            $this->assertEquals('FEMALE', $this->regExpMatcher->test($femaleName));
        }
    }

    public function testNotFoundName()
    {
        $this->assertEquals('UNKNOWN', $this->regExpMatcher->test('ross'));
    }
}

