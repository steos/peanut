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

class Factory {
	private $descriptor;
	private $context;
	function __construct(Descriptor $ds, Context $cx) {
		$this->descriptor = $ds;
		$this->context = $cx;
	}
	function createPeanut() {
		$fc = $this->descriptor->getFactoryClass();
		$class = $this->descriptor->getClass();
		if ($fc == null) {
			$refClass = new \ReflectionClass($class);
		}
		else {
			$refClass = new \ReflectionClass($fc);
		}
		try {
			$instance = $this->createInstance($refClass);
			if (!is_a($instance, $class)) {
				$actual = get_class($instance);
				throw new PeanutException(
					"expected object of type \"$class\" but got \"$actual\"");
			}
			$this->populate($instance);
		}
		catch (\ReflectionException $e) {
			throw new PeanutException($e->getMessage());
		}
		return $instance;
	}
	
	private function populate($instance) {
		$className = get_class($instance);
		$class = new \ReflectionClass($className);
		$props = $this->descriptor->getProperties();
		foreach ($props as $name => $value) {
			if (!$class->hasProperty($name)) {
				throw new PeanutException(
					"unknown property \"$name\" in class \"$className\"");
			}
			$prop = $class->getProperty($name);
			$prop->setAccessible(true);
			$prop->setValue($instance, $this->resolveValue($value));
		}
	}
	
	private function createInstance(\ReflectionClass $class) {
		$method = null;
		$factoryMethod = $this->descriptor->getFactoryMethod();
		if ($factoryMethod == null) {
			$method = $class->getConstructor();
			if ($method == null) {
				return $class->newInstance();
			}
		}
		else {
			if (!$class->hasMethod($factoryMethod)) {
				throw new PeanutException("method \"$factoryMethod\" " . 
					"is undefined in class \"{$class->getName()}\"");
			}
			$method = $class->getMethod($factoryMethod);
		}
		
		$paramMethod = $method;
		if ($factoryMethod != null && !$method->isStatic()) {
			$paramMethod = $class->getConstructor();
		}
		
		$numReqParams = $paramMethod->getNumberOfRequiredParameters();
		$numParams = $paramMethod->getNumberOfParameters();
		$numPeanutParams = $this->descriptor->getParamCount();
		
		if ($numPeanutParams < $numReqParams || $numPeanutParams > $numParams) {
			throw new PeanutException(sprintf(
				'method "%s" takes between %d and %d parameters but ' . 
				'the descriptor specifies %d parameters', 
				$factoryMethod, $numReqParams, $numParams, $numPeanutParams));
		}
		
		if (!$method->isPublic()) {
			$method->setAccessible(true);
		}

		$params = $this->getParamValues();
		
		if ($factoryMethod == null) {
			return $class->newInstanceArgs($params);
		}
		else {
			if (!$method->isStatic()) {
				$instance = $class->newInstanceArgs($params);
				return $method->invoke($instance);
			}
			else {
				return $method->invokeArgs(null, $params);
			}	
		}
	}
	
	private function getParamValues() {
		$params = $this->descriptor->getParams();
		$values = array();
		foreach ($params as &$param) {
			$values[] = $this->resolveValue($param);
		}
		return $values;
	}
	
	private function resolveValue($value) {
		if ($value instanceof DescriptorRef) {
			return $this->context[$value->getId()];
		}
		else if (is_array($value)) {
			$vals = array();
			foreach ($value as $k => $v) {
				$vals[$k] = $this->resolveValue($v);
			}	
			return $vals;
		}
		return $value;
	}
}