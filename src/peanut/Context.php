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
	protected $descriptors;
	function __construct() {
		$this->peanuts = array();
		$this->descriptors = array();
	}
	protected function initEagerPeanuts() {
		foreach ($this->descriptors as &$ds) {
			if (!$ds->isLazy() 
				&& $ds->getType() == Descriptor::TYPE_SINGLETON) {
				$this->peanuts[$ds->getId()] = $this->createPeanut($ds);
			}
		}
	}
	protected function createPeanut(Descriptor $ds) {
		$factory = new Factory($ds, $this);
		$peanut = $factory->createPeanut($ds, $this);
		return $peanut;
	}
	function offsetGet($name) {
		$ds = @$this->descriptors[$name];
		if ($ds != null) {
			if ($ds->getType() == Descriptor::TYPE_SINGLETON 
				&& isset($this->peanuts[$name])) {
				return $this->peanuts[$name];
			}
			$peanut = $this->createPeanut($ds);
			if ($ds->getType() == Descriptor::TYPE_SINGLETON) {
				$this->peanuts[$ds->getId()] = $peanut;
			}
			return $peanut;
		}
		return null;
	}
	function offsetSet($name, $value) {
		if ($value instanceof Descriptor) {
			$this->descriptors[$value->getId()] = $value;
		}
		else {
			throw new InvalidArgumentException(
				"expected Descriptor instance");
		}
	}
	function offsetUnset($name) {
		unset($this->descriptors[$name]);
		unset($this->peanuts[$name]);
	}
	function offsetExists($name) {
		return array_key_exists($name, $this->descriptors);
	}
	function getIterator() {
		return new \ArrayIterator($this->descriptors);
	}
	function __get($name) {
		return $this[$name];
	}
	function getDescriptor($name) {
		return @$this->descriptors[$name];
	}
}
