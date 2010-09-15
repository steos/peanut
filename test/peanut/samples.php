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

class Sample1 {
	private $bar;
	function getBar() {
		return $this->bar;
	}
}

class Sample2 {
	private $bar;
	function __construct(Sample1 $bar) {
		$this->bar = $bar;
	}
	function getBar() {
		return $this->bar;
	}
}

class Sample3 {
	public $bar;
	private function __construct() {
		$this->bar = 'baz';
	}
	static function factory() {
		return new self();
	}
}

class Sample4 {
	static function factory() {
		return new Sample1();
	}
}