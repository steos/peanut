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

require_once 'peanut/Context.php';
require_once 'peanut/XmlContext.php';
require_once 'peanut/Descriptor.php';
require_once 'peanut/TestCase.php';
require_once 'peanut/PeanutException.php';
require_once 'peanut/samples.php';

class DescriptorTest extends TestCase {
	private $context;
	function setUp() {
		parent::setUp();
		$this->context = new XmlContext(null);
	}
	function testSample1() {
		$desc = new Descriptor('foo', 'peanut\Sample1');
		$desc->setProperty('bar', 'baz');
		$obj = $desc->getPeanut($this->context);
		$this->assertTrue($obj instanceof Sample1);
		$this->assertEquals('baz', $obj->getBar());
	}
	
	function testSample2() {
		$desc1 = new Descriptor('foo', 'peanut\Sample1');
		$desc1->setProperty('bar', 'baz');
		$desc2 = new Descriptor('bar', 'peanut\Sample2');
		$desc2->addParam($desc1);
		$sample2 = $desc2->getPeanut($this->context);
		$this->assertTrue($sample2 instanceof Sample2);
		$this->assertEquals($desc1->getPeanut($this->context), $sample2->getBar());
		$this->assertTrue($desc1->getPeanut($this->context) === $sample2->getBar());
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testSample2InvalidParams() {
		$desc = new Descriptor('bar', 'peanut\Sample2');
		$desc->getPeanut($this->context);
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testSample2UnkknownProperty() {
		$desc = new Descriptor('bar', 'peanut\Sample2');
		$desc->addParam(new Sample1());
		$desc->setProperty('foo', 'bar');
		$sample2 = $desc->getPeanut($this->context);
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testSample3PrivateCtor() {
		$desc = new Descriptor('foo', 'peanut\Sample3');
		$sample3 = $desc->getPeanut($this->context);
	}
	
	function testSample3FactoryMethod() {
		$desc = new Descriptor('foo', 'peanut\Sample3');
		$desc->setFactoryMethod('factory');
		$sample3 = $desc->getPeanut($this->context);
		$this->assertTrue($sample3 instanceof Sample3);
		$this->assertEquals('baz', $sample3->bar);
	}
	
	function testSample4FactoryClass() {
		$desc = new Descriptor('foo', 'peanut\Sample4');
		$desc->setFactoryMethod('factory');
		$desc->setProperty('bar', 'baz');
		$sample1 = $desc->getPeanut($this->context);
		$this->assertTrue($sample1 instanceof Sample1);
		$this->assertEquals('baz', $sample1->getBar());
	}
	
	function testPeanutType() {
		$desc = new Descriptor('foo', 'peanut\Sample1');
		$obj1 = $desc->getPeanut($this->context);
		$obj2 = $desc->getPeanut($this->context);
		$this->assertTrue($obj1 === $obj2);
		$desc = new Descriptor('foo', 'peanut\Sample1', 
			Descriptor::TYPE_PROTOTYPE);
		$obj1 = $desc->getPeanut($this->context);
		$obj2 = $desc->getPeanut($this->context);
		$this->assertTrue($obj1 !== $obj2);
	}
	
	function testPeanutProperty() {
		$desc = new Descriptor('foo', 'peanut\Sample1');
		$desc2 = new Descriptor('bar', 'peanut\Sample1');
		$desc->setProperty('bar', $desc2);
		$obj1 = $desc->getPeanut($this->context);
		$this->assertEquals($desc2->getPeanut($this->context), $obj1->getBar());
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testUnknownFactoryMethod() {
		$desc = new Descriptor('foo', 'peanut\Sample1');
		$desc->setFactoryMethod('foobar');
		$desc->getPeanut($this->context);
	}
	
	/**
	 * @expectedException peanut\PeanutException
	 */
	function testNonStaticFactoryMethod() {
		$desc = new Descriptor('foo', 'peanut\Sample2');
		$desc->setFactoryMethod('__construct');
		$desc->getPeanut($this->context);
	}
}