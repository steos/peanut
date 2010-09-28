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
		$cx = new XmlContext($this->resourceDir . '/sample1.xml');
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
		
		$str = $this->readStreamContent($stream);
		
		$this->assertEquals($ex, $str);
	}
	
	private function readStreamContent($stream) {
		fseek($stream, 0);
		$str = '';
		while (!feof($stream)) {
			$str .= fgets($stream);
		}
		return $str;
	} 
}