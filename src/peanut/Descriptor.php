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
	private $params;
	private $properties;
	private $instance;
	
	function __construct($id, $class, $type = self::TYPE_SINGLETON) {
		$this->id = $id;
		$this->class = $class;
		$this->factoryMethod = null;
		$this->params = array();
		$this->properties = array();
		$this->instance = null;
		$this->type = $type;
	}
	
	function setFactoryMethod($method) {
		$this->factoryMethod = $method;
	}
	
	function addParam($param) {
		$this->params[] = $param;
	}
	
	function setProperty($name, $value) {
		$this->properties[$name] = $value;
	}
	
	function getPeanut(Context $cx) {
		if ($this->instance != null && $this->type == self::TYPE_SINGLETON) {
			return $this->instance;
		}
		$refClass = new \ReflectionClass($this->class);
		try {
			$this->instance = $this->createInstance($refClass, $cx);
			$this->populate($cx);
		}
		catch (\ReflectionException $e) {
			throw new PeanutException($e->getMessage());
		}
		return $this->instance;
	}
	
	private function populate(Context $cx) {
		$className = get_class($this->instance);
		$class = new \ReflectionClass($className);
		foreach ($this->properties as $name => $value) {
			if (!$class->hasProperty($name)) {
				throw new PeanutException(
					"unknown property \"$name\" in class \"$className\"");
			}
			$prop = $class->getProperty($name);
			$prop->setAccessible(true);
			$prop->setValue($this->instance, $this->resolveValue($value, $cx));
		}
	}
	
	private function createInstance(\ReflectionClass $class, Context $cx) {
		$method = null;
		if ($this->factoryMethod == null) {
			$method = $class->getConstructor();
			if ($method == null) {
				return $class->newInstance();
			}
		}
		else {
			if (!$class->hasMethod($this->factoryMethod)) {
				throw new PeanutException("method \"$this->factoryMethod\" " . 
					"is undefined in class \"{$class->getName()}\"");
			}
			$method = $class->getMethod($this->factoryMethod);
			if (!$method->isStatic()) {
				throw new PeanutException("cannot use non-static method " . 
					"\"$this->factoryMethod\" as factory");
			}
		}
		
		$numReqParams = $method->getNumberOfRequiredParameters();
		$numParams = $method->getNumberOfParameters();
		$numPeanutParams = count($this->params);
		if ($numPeanutParams < $numReqParams || $numPeanutParams > $numParams) {
			throw new PeanutException(sprintf(
				'method "%s" takes between %d and %d parameters but ' . 
				'the descriptor specifies %d parameters', 
				$this->factoryMethod, 
				$numReqParams, $numParams, $numPeanutParams));
		}
		
		if (!$method->isPublic()) {
			$method->setAccessible(true);
		}

		$params = array();
		foreach ($this->params as &$param) {
			$value = $this->resolveValue($param, $cx);
			$params[] = $value;
		}
		
		if ($this->factoryMethod == null) {
			return $class->newInstanceArgs($params);
		}
		else {
			return $method->invokeArgs(null, $params);	
		}
	}
	
	private function resolveValue($value, Context $cx) {
		if ($value instanceof Descriptor) {
			$val = $value->getPeanut($cx);
		}
		else if ($value instanceof DescriptorRef) {
			$val = $cx[$value->getId()];
		}
		else {
			$val = $value;
			if (is_array($val)) {
				foreach ($val as &$v) {
					$v = $this->resolveValue($v, $cx);
				}
			}
		}
		return $val;
	}
	
	function getId() {
		return $this->id;
	}
	
	function getClass() {
		return $this->class;
	}
}