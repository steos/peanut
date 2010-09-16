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

abstract class Context implements \ArrayAccess, \IteratorAggregate {
	protected $peanuts;
	function __construct() {
		$this->peanuts = array();
	}
	function offsetGet($name) {
		$ds = @$this->peanuts[$name];
		if ($ds != null) {
			return $ds->getPeanut($this);
		}
		return null;
	}
	function offsetSet($name, $value) {
		$this->peanuts[$name] = $value;
	}
	function offsetUnset($name) {
		unset($this->peanuts[$name]);
	}
	function offsetExists($name) {
		return array_key_exists($name, $this->properties);
	}
	function getIterator() {
		return new ArrayIterator($this->peanuts);
	}
	function __get($name) {
		return $this[$name];
	}
}