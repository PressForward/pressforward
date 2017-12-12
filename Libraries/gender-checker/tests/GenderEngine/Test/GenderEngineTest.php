<?php

namespace GenderEngine\Test;

use GenderEngine\GenderEngine as GenderEngine;

class GenderEngineTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->matcher = new GenderEngine();
    }

    public function tearDown()
    {
        $this->matcher = null;
    }

    public function testCreateAllDefaultsInstance()
    {
        $matcher = new GenderEngine();
    }

    public function testCreateCustomConfidenceInstance()
    {
        $matcher = new GenderEngine(3);
        $this->assertEquals(3, $matcher->getConfidence());
    }

    public function testCreateInvalidCustomConfidenceInstance()
    {
        $this->setExpectedException('UnexpectedValueException');
        $matcher = new GenderEngine('1');
    }

    public function testCreateCustomMaleListInstance()
    {
        $matcher = new GenderEngine(null,  __DIR__ . '/../Fixtures/TestValidMaleNames.json', null, false);
        $this->assertCount(2, $matcher->getMaleList());
    }

    public function testCreateUnknownCustomMaleListInstance()
    {
        $this->setExpectedException('FileNotFoundException');
        $matcher = new GenderEngine(null, 'UnknownMaleNames.json', null, false);
    }

    public function testCreateFileEmptyCustomMaleListInstance()
    {
        $this->setExpectedException('GenderEngine\Exceptions\FileEmptyException');
        $matcher = new GenderEngine(null,  __DIR__ . '/../Fixtures/TestEmptyMaleNames.json', null, false);
    }

    public function testCreateInvalidCustomMaleListInstance()
    {
        $this->setExpectedException('GenderEngine\Exceptions\JSONDecodeException');
        $matcher = new GenderEngine(null,  __DIR__ . '/../Fixtures/TestInvalidMaleNames.json', null, false);
    }

    public function testDefaultPreviousConfidence()
    {
        $this->assertEquals(-1, $this->matcher->getPreviousMatchConfidence());
    }

    public function testDefaultMatcherConfidenceOrder()
    {
        $this->assertEquals([
            'ListMatch',
            'ListWeightedMatch',
            'MetaphoneMatch',
            'MetaphoneWeightedMatch',
            'RegExpV2Match',
            'RegExpV1Match',
            'RestNamesWSMatch',
            'BabyNamesWSMatch'
        ], $this->matcher->getMatcherConfidenceOrder());
    }

    public function testSetMatcherConfidenceOrder()
    {
        $this->matcher->setMatcherConfidenceOrder([
            'ListMatch',
            'ListWeightedMatch'
        ]);

        $this->assertEquals([
            'ListMatch',
            'ListWeightedMatch'
        ], $this->matcher->getMatcherConfidenceOrder());
    }

    public function testInvalidSubjectNameTest()
    {
        $this->setExpectedException('UnexpectedValueException');
        $this->matcher->test(1234);
    }

    public function testEmptySubjectNameTest()
    {
        $this->setExpectedException('UnexpectedValueException');
        $this->matcher->test('');
    }

    public function testInvalidMatcherNameTest()
    {
        $this->setExpectedException('GenderEngine\Exceptions\InvalidMatcherException');
        $this->matcher->setMatcherConfidenceOrder([
            'InvalidMatcher'
        ]);
        $this->matcher->test('bob');
    }

    public function testKnownMaleNameTest()
    {
        $this->assertEquals('MALE', $this->matcher->test('brad'));
    }

    public function testKnownFemaleNameTest()
    {
        $this->assertEquals('FEMALE', $this->matcher->test('sally'));
    }

    public function testUnknownNameTest()
    {
        $this->assertEquals('UNKNOWN', $this->matcher->test('43dfjkfdjkd'));
    }
}

