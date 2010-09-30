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

require_once 'peanut/Descriptor.php';
require_once 'peanut/DescriptorRef.php';
require_once 'peanut/Context.php';
require_once 'peanut/XmlContext.php';
require_once 'peanut/samples.php';
require_once 'peanut/ContextCompiler.php';
require_once 'peanut/TestCase.php';

class ContextCompilerTest extends TestCase {
	function testSample1() {
		$cx = XmlContext::fromFile($this->resourceDir . '/sample1.xml');
		$cx->load();
		
		$compiler = new ContextCompiler($cx);
		$stream = fopen('php://temp', 'w');
		$compiler->compile($stream);
		
		$ex = <<<PHP
\$foo = new peanut\Sample1();
\$foo->setBar('foobar');
\$bar = new peanut\Sample2(\$foo);
\$baz = new peanut\Sample1();
\$baz->setBar(array('foo' => 'lorem', 'bar' => 'ipsum'));
\$nested = new peanut\Sample1();
\$nested->setBar(array(0 => 'foobar', 1 => \$foo, 2 => array(0 => 'lorem', 1 => 'ipsum'), 3 => array('lorem' => 'ipsum', 'foo' => \$foo)));
\$lorem = peanut\Sample3::factory();
\$ipsum = peanut\Sample4::factory();
\$ipsum->setBar('foobar');

PHP;
		
		$str = stream_get_contents($stream, -1, 0);
		
		$this->assertEquals($ex, $str);
	}
	
	function testPrototypes() {
		$cx = XmlContext::fromFile("$this->resourceDir/sample2.xml");
		$cx->load();
		$compiler = new ContextCompiler($cx);
		$stream = fopen('php://temp', 'w');
		$compiler->compile($stream);
		
		$ex = <<<PHP
\$foo_0 = new peanut\Sample1();
\$foo_0->setBar('foobar');
\$foo_1 = new peanut\Sample1();
\$foo_1->setBar('foobar');
\$bar = new peanut\Sample2(\$foo_1);
\$foo_2 = new peanut\Sample1();
\$foo_2->setBar('foobar');
\$foo_3 = new peanut\Sample1();
\$foo_3->setBar('foobar');
\$baz = new peanut\Sample2(array(0 => \$foo_2, 1 => 'bar', 2 => \$foo_3));

PHP;
		$this->assertEquals($ex, stream_get_contents($stream, -1, 0));
	}
}