<?php

namespace GenderEngine\Test\Matchers;

use GenderEngine\Matchers\MetaphoneWeightedMatch as MetaphoneWeightedMatch;

class MetaphoneWeightedMatchTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->maleNames = [
            'bobby'  => 1,
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

        $this->listMatcher = new MetaphoneWeightedMatch();
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
        $this->listMatcher->setNameLists($this->maleNames, $this->femaleNames);

        $this->assertEquals('MALE', $this->listMatcher->test('bobbie'));    
    }

    public function testFoundFemaleNames()
    {
        $this->listMatcher->setNameLists($this->maleNames, $this->femaleNames);

        $this->assertEquals('FEMALE', $this->listMatcher->test('sallie'));
    }

    public function testFoundMaleAndFemaleName()
    {
        $this->listMatcher->setNameLists($this->maleNames, $this->femaleNames);

        $this->assertEquals('FEMALE', $this->listMatcher->test('ashlie'));
    }

    public function testNotFoundName()
    {
        $this->listMatcher->setNameLists($this->maleNames, $this->femaleNames);
        
        $this->assertEquals('UNKNOWN', $this->listMatcher->test('zack'));
    }
}

