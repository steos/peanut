<?php
/* This file is part of Peanut.
 *
 * Peanut is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * Peanut is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Peanut. If not, see <http://www.gnu.org/licenses/>.
 */

namespace peanut;

require_once 'peanut/Factory.php';
require_once 'peanut/Context.php';
require_once 'peanut/XmlContext.php';
require_once 'peanut/Descriptor.php';
require_once 'peanut/DescriptorRef.php';
require_once 'peanut/TestCase.php';
require_once 'peanut/PeanutException.php';
require_once 'peanut/samples.php';

class DummyContext extends Context {}

class DescriptorTest extends TestCase {
	private $context;
	function setUp() {
		parent::setUp();
		$this->context = new DummyContext();
	}
	function getPeanut($id) {
		return $this->context[$id];
	}
	function createPeanut($id, $class, $type = Descriptor::TYPE_SINGLETON) {
		$ds = new Descriptor($id, $class, $type);
		$this->context[$id] = $ds;
		return $ds;	
	}
	
	function testSample1() {
		$desc = $this->createPeanut('foo', 'peanut\Sample1');
		$desc->setProperty('bar', 'baz');
		$obj = $this->getPeanut('foo');
		$this->assertTrue($obj instanceof Sample1);
		$this->assertEquals('baz', $obj->getBar());
	}
	
	function testSample2() {
		$desc1 = $this->createPeanut('foo', 'peanut\Sample1');
		$desc1->setProperty('bar', 'baz');
		$desc2 = $this->createPeanut('bar', 'peanut\Sample2');
		$desc2->addParam(new DescriptorRef('foo'));
		$sample2 = $this->getPeanut('bar');
		$this->assertTrue($sample2 instanceof Sample2);
		$this->assertEquals($this->getPeanut('foo'), $sample2->getBar());
		$this->assertTrue($this->getPeanut('foo') === $sample2->getBar());
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testSample2InvalidParams() {
		$desc = $this->createPeanut('bar', 'peanut\Sample2');
		$this->getPeanut('bar');
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testSample2UnkknownProperty() {
		$desc = $this->createPeanut('bar', 'peanut\Sample2');
		$desc->addParam(new Sample1());
		$desc->setProperty('foo', 'bar');
		$sample2 = $this->getPeanut('bar');
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testSample3PrivateCtor() {
		$desc = $this->createPeanut('foo', 'peanut\Sample3');
		$sample3 = $this->getPeanut('foo');
	}
	
	function testSample3FactoryMethod() {
		$desc = $this->createPeanut('foo', 'peanut\Sample3');
		$desc->setFactoryMethod('factory');
		$sample3 = $this->getPeanut('foo');
		$this->assertTrue($sample3 instanceof Sample3);
		$this->assertEquals('baz', $sample3->bar);
	}
	
	function testSample4FactoryClass() {
		$desc = $this->createPeanut('foo', 'peanut\Sample1');
		$desc->setFactoryMethod('factory');
		$desc->setFactoryClass('peanut\Sample4');
		$desc->setProperty('bar', 'baz');
		$sample1 = $this->getPeanut('foo');
		$this->assertTrue($sample1 instanceof Sample1);
		$this->assertEquals('baz', $sample1->getBar());
	}
	
	function testSample5FactoryClass() {
		$foo = $this->createPeanut('foo', 'peanut\Sample1');
		$ds = $this->createPeanut('bar', 'peanut\Sample2');
		$ds->setFactoryClass('peanut\Sample5');
		$ds->setFactoryMethod('factory');
		$ds->addParam(new DescriptorRef('foo'));
		$sample2 = $this->getPeanut('bar');
		$this->assertTrue($sample2 instanceof Sample2);
		$this->assertEquals($this->getPeanut('foo'), $sample2->getBar());
	}
	
	function testPeanutType() {
		$desc = $this->createPeanut('foo', 'peanut\Sample1');
		$obj1 = $this->getPeanut('foo');
		$obj2 = $this->getPeanut('foo');
		$this->assertTrue($obj1 === $obj2, 
			'peanuts are not identical but should be');
		$desc = $this->createPeanut('foo', 'peanut\Sample1', 
			Descriptor::TYPE_PROTOTYPE);
		$obj1 = $this->getPeanut('foo');
		$obj2 = $this->getPeanut('foo');
		$this->assertTrue($obj1 !== $obj2, 
			'peanuts are identical but shouldn\'t be');
	}
	
	function testPeanutProperty() {
		$desc = $this->createPeanut('foo', 'peanut\Sample1');
		$desc2 = $this->createPeanut('bar', 'peanut\Sample1');
		$desc->setProperty('bar', new DescriptorRef('bar'));
		$obj1 = $this->getPeanut('foo');
		$this->assertEquals($this->getPeanut('bar'), $obj1->getBar());
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testUnknownFactoryMethod() {
		$desc = $this->createPeanut('foo', 'peanut\Sample1');
		$desc->setFactoryMethod('foobar');
		$this->getPeanut('foo');
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testNonStaticFactoryMethod() {
		$desc = $this->createPeanut('foo', 'peanut\Sample2');
		$desc->setFactoryMethod('__construct');
		$this->getPeanut('foo');
	}
}