<?php

namespace GenderEngine\Test\Matchers;

use GenderEngine\Matchers\ListMatch as ListMatch;

class ListMatchTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->maleNames = [
            'bob'    => 1,
            'jeff'   => 1,
            'jack'   => 1,
            'ashley' => 1
        ];

        $this->femaleNames = [
            'sally'  => 1,
            'jackie' => 1,
            'julie'  => 1,
            'ashley' => 2
        ];

        $this->listMatcher = new ListMatch();
    }

    public function tearDown()
    {
    }

    public function testInheritsMatcher()
    {
        $this->assertInstanceOf('GenderEngine\Matchers\Interfaces\Matcher', $this->listMatcher);
    }

    public function testSetMaleNameList()
    {
        $this->listMatcher->setMaleNameList($this->maleNames);

        $this->assertEquals($this->maleNames, $this->listMatcher->getMaleNameList());
    }

    public function testSetFemaleNameList()
    {
        $this->listMatcher->setFemaleNameList($this->femaleNames);

        $this->assertEquals($this->femaleNames, $this->listMatcher->getFemaleNameList());
    }

    public function testSetNameLists()
    {
        $this->listMatcher->setNameLists($this->maleNames, $this->femaleNames);

        $this->assertEquals($this->maleNames, $this->listMatcher->getMaleNameList());
        $this->assertEquals($this->femaleNames, $this->listMatcher->getFemaleNameList());
    }

    public function testFoundMaleNames()
    {
        $foundMaleNames = ['bob', 'jeff', 'jack'];

        $this->listMatcher->setNameLists($this->maleNames, $this->femaleNames);

        foreach ($foundMaleNames as $maleName) {
            $this->assertEquals('MALE', $this->listMatcher->test($maleName));
        }
    }

    public function testFoundFemaleNames()
    {
        $foundFemaleNames = ['sally', 'jackie', 'julie'];

        $this->listMatcher->setNameLists($this->maleNames, $this->femaleNames);

        foreach ($foundFemaleNames as $femaleName) {
            $this->assertEquals('FEMALE', $this->listMatcher->test($femaleName));
        }
    }

    public function testFoundMaleAndFemaleName()
    {
        $this->listMatcher->setNameLists($this->maleNames, $this->femaleNames);

        $this->assertEquals('UNKNOWN', $this->listMatcher->test('ashley'));
    }

    public function testNotFoundName()
    {
        $this->listMatcher->setNameLists($this->maleNames, $this->femaleNames);
        
        $this->assertEquals('UNKNOWN', $this->listMatcher->test('zack'));
    }
}

