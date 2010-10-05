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

class Descriptor {
	const TYPE_SINGLETON = 1;
	const TYPE_PROTOTYPE = 2;
	
	private $id;
	private $class;
	private $factoryMethod;
	private $factoryClass;
	private $params;
	private $properties;
	private $instance;
	private $lazy;
	
	function __construct($id, $class, $type = self::TYPE_SINGLETON) {
		$this->id = $id;
		$this->class = $class;
		$this->factoryMethod = null;
		$this->factoryClass = null;
		$this->params = array();
		$this->properties = array();
		$this->instance = null;
		$this->type = $type;
		$this->lazy = true;
	}
	
	function setLazy($lazy) {
		$this->lazy = $lazy;
	}
	
	function isLazy() {
		return $this->lazy;
	}
	
	function setFactoryMethod($method) {
		$this->factoryMethod = $method;
	}
	
	function setFactoryClass($class) {
		$this->factoryClass = $class;
	}
	
	function getFactoryClass() {
		return $this->factoryClass;
	}
	
	function addParam($param) {
		$this->params[] = $param;
	}
	
	function setProperty($name, $value) {
		$this->properties[$name] = $value;
	}
	
	function getId() {
		return $this->id;
	}
	
	function getClass() {
		return $this->class;
	}
	
	function getType() {
		return $this->type;
	}
	
	function getFactoryMethod() {
		return $this->factoryMethod;
	}
	
	function getParamCount() {
		return count($this->params);
	}
	
	function getParams() {
		return $this->params;
	}
	
	function getProperties() {
		return $this->properties;
	}
}