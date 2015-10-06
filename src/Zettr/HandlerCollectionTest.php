<?php

namespace Zettr;


class HandlerCollectionTest extends \PHPUnit_Framework_TestCase {


    /**
     * @test
     */
    public function canBuildFromCSVAndReplaceByEnvironmentVariable() {

        putenv('DEBUG=TESTCONTENT');
        $handlerCollection = $this->getHandlerCollectionFromFixture('Settings.csv');

        $handlers=array();
        foreach ($handlerCollection as $handler) {
            $handlers[]=$handler;
        }

        $this->assertEquals(2,count($handlers));
        $handler1 = $handlers[0]; /* @var $handler1 Handler\AbstractHandler */
        $this->assertTrue($handler1 instanceof Handler\XmlFile);
        $this->assertEquals($handler1->getValue(),'latestdb');

        $handler2 = $handlers[1]; /* @var $handler2 Handler\AbstractHandler */
        $this->assertEquals($handler2->getValue(),'TESTCONTENT','either did not use fallback content or replacement with ENVVariable did not work');
    }

    /**
     * @test
     */
    public function canUseDefaultValues() {
        /*
            \Zettr\Handler\Magento\CoreConfigData,1,foo,bar,defaultvalue,
            \Zettr\Handler\Magento\CoreConfigData,2,foo,bar,defaultvalue,,
            \Zettr\Handler\Magento\CoreConfigData,3,foo,bar,defaultvalue,0,
            \Zettr\Handler\Magento\CoreConfigData,4,foo,bar,defaultvalue, ,
            \Zettr\Handler\Magento\CoreConfigData,5,foo,bar,,
        */
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithDefaultValues.csv');

        $this->assertHandlerValue('defaultvalue', $handlerCollection, 'Zettr\Handler\Magento\CoreConfigData', 1, 'foo', 'bar');
        $this->assertHandlerValue('defaultvalue', $handlerCollection,'Zettr\Handler\Magento\CoreConfigData', 1,'foo','bar');
        $this->assertHandlerValue('defaultvalue', $handlerCollection,'Zettr\Handler\Magento\CoreConfigData', 2,'foo','bar');
        $this->assertHandlerValue(0, $handlerCollection, 'Zettr\Handler\Magento\CoreConfigData',3,'foo','bar');
        $this->assertHandlerValue(' ', $handlerCollection, 'Zettr\Handler\Magento\CoreConfigData',4,'foo','bar');
        $this->assertHandlerValue('', $handlerCollection, 'Zettr\Handler\Magento\CoreConfigData',5,'foo','bar');
        $this->assertHandlerValue('', $handlerCollection, 'Zettr\Handler\Magento\CoreConfigData',6,'foo','bar');
    }

    /**
     * Assert handler value
     *
     * @param $expectedValue
     * @param HandlerCollection $handlerCollection
     * @param $handlerClass
     * @param $param1
     * @param $param2
     * @param $param3
     */
    public function assertHandlerValue($expectedValue, HandlerCollection $handlerCollection, $handlerClass, $param1, $param2, $param3) {
        $handler = $handlerCollection->getHandler($handlerClass, $param1, $param2, $param3);
        $this->assertInstanceOf('\Zettr\Handler\HandlerInterface', $handler, 'Could not find handler: ' . $handlerClass);
        $this->assertEquals($expectedValue, $handler->getValue());
    }

    /**
     * @test
     */
    public function canGetHandler() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('Settings.csv');
        $handler = $handlerCollection->getHandler('Zettr\Handler\XmlFile','app/etc/local.xml','/config/global/resources/default_setup/connection/host','');
        $this->assertTrue($handler instanceof Handler\XmlFile);
    }

    /**
     * @test
     */
    public function canUseHandlersWithOneLoopParams() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithOneLoop.csv');

        $handlers = array();
        foreach ($handlerCollection as $handler) {
            $handlers[] = $handler;
        }

        $this->assertCount(2, $handlers);

        $this->assertEquals('default', $handlers[0]->getParam1());
        $this->assertEquals('default', $handlers[1]->getParam1());

        $this->assertEquals('1', $handlers[0]->getParam2());
        $this->assertEquals('2', $handlers[1]->getParam2());

        $this->assertEquals('dev/debug/profiler', $handlers[0]->getParam3());
        $this->assertEquals('dev/debug/profiler', $handlers[1]->getParam3());

        $this->assertEquals('test2', $handlers[0]->getValue());
        $this->assertEquals('test2', $handlers[1]->getValue());
    }

    /**
     * @test
     */
    public function canUseHandlersWithLoopWithEmptyValue() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithLoopWithEmptyValue.csv');

        $handlers = array();
        foreach ($handlerCollection as $handler) {
            $handlers[] = $handler;
        }

        $this->assertCount(3, $handlers);

        $this->assertEquals('default', $handlers[0]->getParam1());
        $this->assertEquals('default', $handlers[1]->getParam1());
        $this->assertEquals('default', $handlers[2]->getParam1());

        $this->assertEquals('test_1', $handlers[0]->getParam2());
        $this->assertEquals('test_2', $handlers[1]->getParam2());
        $this->assertEquals('test_', $handlers[2]->getParam2());

        $this->assertEquals('dev/debug/profiler', $handlers[0]->getParam3());
        $this->assertEquals('dev/debug/profiler', $handlers[1]->getParam3());
        $this->assertEquals('dev/debug/profiler', $handlers[2]->getParam3());

        $this->assertEquals('test2', $handlers[0]->getValue());
        $this->assertEquals('test2', $handlers[1]->getValue());
        $this->assertEquals('test2', $handlers[2]->getValue());
    }

    /**
     * @test
     */
    public function canUseHandlersWithTwoLoopParams() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithTwoLoops.csv');

        $handlers = array();
        foreach ($handlerCollection as $handler) {
            $handlers[] = $handler;
        }

        $this->assertCount(4, $handlers);

        $this->assertEquals('store', $handlers[0]->getParam1());
        $this->assertEquals('store', $handlers[1]->getParam1());
        $this->assertEquals('website', $handlers[2]->getParam1());
        $this->assertEquals('website', $handlers[3]->getParam1());

        $this->assertEquals('1', $handlers[0]->getParam2());
        $this->assertEquals('2', $handlers[1]->getParam2());
        $this->assertEquals('1', $handlers[2]->getParam2());
        $this->assertEquals('2', $handlers[3]->getParam2());

        $this->assertEquals('dev/debug/profiler', $handlers[0]->getParam3());
        $this->assertEquals('dev/debug/profiler', $handlers[1]->getParam3());
        $this->assertEquals('dev/debug/profiler', $handlers[2]->getParam3());
        $this->assertEquals('dev/debug/profiler', $handlers[3]->getParam3());

        $this->assertEquals('test2', $handlers[0]->getValue());
        $this->assertEquals('test2', $handlers[1]->getValue());
        $this->assertEquals('test2', $handlers[2]->getValue());
        $this->assertEquals('test2', $handlers[3]->getValue());
    }

    /**
     * @test
     */
    public function canUseHandlersWithTwoLoopParamsInTheSameParameter() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithTwoLoopsInTheSameParameter.csv');

        $handlers = array();
        foreach ($handlerCollection as $handler) {
            $handlers[] = $handler;
        }

        $this->assertCount(4, $handlers);

        $this->assertEquals('test_store_a', $handlers[0]->getParam1());
        $this->assertEquals('test_store_b', $handlers[1]->getParam1());
        $this->assertEquals('test_website_a', $handlers[2]->getParam1());
        $this->assertEquals('test_website_b', $handlers[3]->getParam1());

        $this->assertEquals('1', $handlers[0]->getParam2());
        $this->assertEquals('1', $handlers[1]->getParam2());
        $this->assertEquals('1', $handlers[2]->getParam2());
        $this->assertEquals('1', $handlers[3]->getParam2());

        $this->assertEquals('dev/debug/profiler', $handlers[0]->getParam3());
        $this->assertEquals('dev/debug/profiler', $handlers[1]->getParam3());
        $this->assertEquals('dev/debug/profiler', $handlers[2]->getParam3());
        $this->assertEquals('dev/debug/profiler', $handlers[3]->getParam3());

        $this->assertEquals('test2', $handlers[0]->getValue());
        $this->assertEquals('test2', $handlers[1]->getValue());
        $this->assertEquals('test2', $handlers[2]->getValue());
        $this->assertEquals('test2', $handlers[3]->getValue());
    }

    /**
     * @test
     */
    public function canUseReferencesToOtherColumns() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithReferences.csv', 'environment_b');

        $handlers = array();
        foreach ($handlerCollection as $handler) {
            $handlers[] = $handler;
        }

        $this->assertCount(1, $handlers);
        $this->assertHandlerValue('foo', $handlerCollection, 'Zettr\Handler\Magento\CoreConfigData', 'p1', 'p2', 'p3');
    }

    /**
     * @test
     */
    public function canUseHandlersWithInlineLoop() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithInlineLoop.csv');

        $handlers = array();
        foreach ($handlerCollection as $handler) {
            $handlers[] = $handler;
        }

        $this->assertCount(3, $handlers);

        $this->assertEquals('param1', $handlers[0]->getParam1());
        $this->assertEquals('param1', $handlers[1]->getParam1());
        $this->assertEquals('param1', $handlers[2]->getParam1());

        $this->assertEquals('param2', $handlers[0]->getParam2());
        $this->assertEquals('param2', $handlers[1]->getParam2());
        $this->assertEquals('param2', $handlers[2]->getParam2());

        $this->assertEquals('a/b/c', $handlers[0]->getParam3());
        $this->assertEquals('a/b/d', $handlers[1]->getParam3());
        $this->assertEquals('a/b/e', $handlers[2]->getParam3());

        $this->assertEquals('test2', $handlers[0]->getValue());
        $this->assertEquals('test2', $handlers[1]->getValue());
        $this->assertEquals('test2', $handlers[2]->getValue());
    }

    /**
     * @test
     */
    public function settingsWithVariable() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithVariables.csv');

        $handlers = array();
        foreach ($handlerCollection as $handler) {
            $handlers[] = $handler;
        }

        $this->assertCount(6, $handlers);

        $this->assertEquals('value2', $handlers[2]->getValue());
        $this->assertEquals('value1', $handlers[3]->getValue());
        $this->assertEquals('inline-value1-usage', $handlers[4]->getValue());
        $this->assertEquals('value1', $handlers[5]->getValue());
    }

    /**
     * @test
     */
    public function settingsWithVariableNotSet() {
        $this->setExpectedException('Exception', 'Variable "notset" is not set');
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithVariablesNotSet.csv');
    }

    /**
     * @test
     */
    public function settingsWithCommentsAndEmptyLines() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithEmptyLinesAndComments.csv');

        $handlers = array();
        foreach ($handlerCollection as $handler) {
            $handlers[] = $handler;
        }

        $this->assertCount(5, $handlers);
    }

    /**
     * @test
     */
    public function exceptionWhenSameSignature() {
        $this->setExpectedException('Exception');
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithSameParameters.csv');
    }

    /**
     * @test
     */
    public function settingsWithMarker() {
        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithMarkers.csv');

        $handlers = array();
        foreach ($handlerCollection as $handler) {
            $handlers[] = $handler;
        }

        $this->assertCount(6, $handlers);

        for ($i=0; $i<6; $i++) {
            $this->assertEquals($i+1, $handlers[$i]->getParam1());
            $this->assertEquals('foo', $handlers[$i]->getParam2());
            $this->assertEquals('bar', $handlers[$i]->getParam3());
        }

        $this->assertEquals('1', $handlers[0]->getValue());
        $this->assertEquals('foo', $handlers[1]->getValue());
        $this->assertEquals('bar', $handlers[2]->getValue());
        $this->assertEquals('latest', $handlers[3]->getValue());
        $this->assertEquals('latest', $handlers[4]->getValue());
        $this->assertEquals('inline-6-parameter', $handlers[5]->getValue());
    }

    /**
     * @test
     * @dataProvider groupProvider
     */
    public function groups($includeGroups, $excludeGroups, $expectedParams) {

        $includeGroups = $includeGroups ? explode(',', $includeGroups) : array();
        $excludeGroups = $excludeGroups ? explode(',', $excludeGroups) : array();
        $expectedParams = $expectedParams ? explode(',', $expectedParams) : array();

        $handlerCollection = $this->getHandlerCollectionFromFixture('SettingsWithGroups.csv', 'testenv', $includeGroups, $excludeGroups);
        $params = array();
        foreach ($handlerCollection as $handler) { /* @var $handler Est_Handler_Abstract */
            $params[] = $handler->getParam2();
        }

        $this->assertEquals($expectedParams, $params);
    }

    public function groupProvider() {
        return array(

            // none
            array('', '', '/a,/b,/c,/a/b,/a/c,/b/c,/a/b/c,/nogroup'), // all rows!

            // includes
            array('groupA', '', '/a,/a/b,/a/c,/a/b/c'),
            array('groupB', '', '/b,/a/b,/b/c,/a/b/c'),
            array('groupC', '', '/c,/a/c,/b/c,/a/b/c'),
            array('groupA,groupB', '', '/a,/b,/a/b,/a/c,/b/c,/a/b/c'), // multiple groups are combined with 'or' not 'and'!
            array('groupB,groupC', '', '/b,/c,/a/b,/a/c,/b/c,/a/b/c'),
            array('groupA,groupC', '', '/a,/c,/a/b,/a/c,/b/c,/a/b/c'),
            array('groupA,groupB,groupC', '', '/a,/b,/c,/a/b,/a/c,/b/c,/a/b/c'),
            array('groupC,groupB', '', '/b,/c,/a/b,/a/c,/b/c,/a/b/c'), // different order groups results in same order of handlers

            // excludes
            array('', 'groupA', '/b,/c,/b/c,/nogroup'),
            array('', 'groupB', '/a,/c,/a/c,/nogroup'),
            array('', 'groupC', '/a,/b,/a/b,/nogroup'),
            array('', 'groupA,groupB', '/c,/nogroup'), // multiple groups are combined with 'and' not 'or'!
            array('', 'groupA,groupB,groupC', '/nogroup'),

            // combination
            array('groupA', 'groupB', '/a,/a/c'),
            array('groupA', 'groupB,groupC', '/a'),
            array('groupA', 'groupA', ''),

        );
    }

    /**
     * Get handler collection from fixture
     *
     * @param string $file
     * @param string $environment
     * @return HandlerCollection
     */
    private function getHandlerCollectionFromFixture($file, $environment='latest', array $includeGroups=array(), array $excludeGroups=array()) {
        $path = FIXTURE_ROOT . $file;
        $handlerCollection = new HandlerCollection();
        $handlerCollection->buildFromSettingsCSVFile($path, $environment, 'DEFAULT', $includeGroups, $excludeGroups);
        return $handlerCollection;
    }


}
