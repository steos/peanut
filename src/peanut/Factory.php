<?php

namespace peanut;

class Factory {
	private $descriptor;
	private $context;
	function __construct(Descriptor $ds, Context $cx) {
		$this->descriptor = $ds;
		$this->context = $cx;
	}
	function createPeanut() {
		$refClass = new \ReflectionClass($this->descriptor->getClass());
		try {
			$instance = $this->createInstance($refClass);
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
			if (!$method->isStatic()) {
				throw new PeanutException("cannot use non-static method " . 
					"\"$factoryMethod\" as factory");
			}
		}
		
		$numReqParams = $method->getNumberOfRequiredParameters();
		$numParams = $method->getNumberOfParameters();
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
			return $method->invokeArgs(null, $params);	
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