<?php

namespace peanut;

require_once 'peanut/Descriptor.php';
require_once 'peanut/DescriptorRef.php';
require_once 'peanut/Context.php';
require_once 'peanut/XmlContext.php';
require_once 'peanut/samples.php';

class XmlContextTest extends TestCase {
	function testSample1() {
		$cx = XmlContext::fromFile($this->resourceDir . '/sample1.xml');

		$this->assertEquals('foobar', $cx->foo->getBar());
		
		$this->assertEquals($cx->foo, $cx->bar->getBar());
		
		$this->assertEquals(array(
			'foo' => 'lorem', 'bar' => 'ipsum'), $cx->baz->getBar());
		
		$ex = array(
			'foobar',
			$cx->foo,
			array('lorem', 'ipsum'),
			array('lorem' => 'ipsum', 'foo' => $cx->foo)
		);
		$this->assertEquals($ex, $cx->nested->getBar());
		
		$this->assertEquals('baz', $cx->lorem->bar);
		
		$this->assertEquals('foobar', $cx->ipsum->getBar());
		
	}
	
	function testAnonymousPeanuts() {
		$xml = <<<XML
<peanuts>
	<peanut class="peanut\Sample1"/>
</peanuts>
XML;
		$cx = XmlContext::fromString($xml);
		$iter = $cx->getIterator();
		$this->assertEquals(1, $iter->count());
	}
}