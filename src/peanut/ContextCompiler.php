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

class ContextCompiler {
	private $context;
	private $stream;
	private $identifiers;
	private $ast;
	function __construct(Context $cx) {
		$this->context = $cx;
		$this->stream = null;
		$this->identifiers = array();
		$this->ast = array();
	}
	function compile($stream) {
		$this->stream = $stream;
		foreach ($this->context as $ds) {
			$this->compilePeanut($ds);
		}
		$this->emitAst();
	}
	
	private function emitAst() {
		foreach ($this->ast as $node) {
			$this->emit("\$$node->name = $node->ctor(");
			$this->emit(implode(', ', $node->params));
			$this->emit(");\n");
			foreach ($node->properties as $name => $value) {
				$setter = 'set' . ucfirst($name);
				$this->emit("\${$node->name}->$setter($value);\n");
			}
		}
	}
	
	private function emit($str) {
		fwrite($this->stream, $str);
	}
	
	private function compilePeanut(Descriptor $ds) {
		$id = $ds->getId();
		$class = $ds->getClass();
		$varName = $ds->getType() == Descriptor::TYPE_SINGLETON ?
			$id : $this->nextUniqueIdentifier($id);
			
		$node = new \stdClass;
		$node->name = $varName;
		$fm = $ds->getFactoryMethod();
		
		if ($fm == null) {
			$node->ctor = "new $class";
		}
		else {
			$node->ctor = "{$class}::$fm";
		}
		
		$node->params = $this->compileParams($ds);
		
		$node->properties = $this->compileProperties($ds);
		
		$this->ast[] = $node;
		
		return $node;
	}
	
	private function compileProperties(Descriptor $ds) {
		$props = $ds->getProperties();
		$res = array();
		foreach ($props as $name => $value) {
			$res[$name] = $this->compileValue($value);
		}
		return $res;
	}
	
	private function compileParams(Descriptor $ds) {
		$params = $ds->getParams();
		$values = array();
		$num = count($params);
		foreach ($params as $param) {
			$values[] = $this->compileValue($param);
		}
		return $values;
	}
	
	private function compileValue($value) {
		if ($value instanceof DescriptorRef) {
			$ds = $this->context->getDescriptor($value->getId());
			if ($ds->getType() == Descriptor::TYPE_SINGLETON) {
				return '$' . $ds->getId();
			}
			else {
				$node = $this->compilePeanut($ds);
				return "\$$node->name";
			}
		}
		else if (is_array($value)) {
			$values = array();
			foreach ($value as $key => $val) {
				if (is_string($key)) {
					$key = "'" . addcslashes($key, "'") . "'";
				}
				$values[] = $key . ' => ' . $this->compileValue($val);
			}
			return 'array(' . implode(', ', $values) . ')';
		}
		else {
			return $this->compileScalar($value);
		}
	}
	
	private function compileScalar($value) {
		switch (gettype($value)) {
			case 'NULL':
				return 'null';
			case 'integer':
			case 'double':
				return $value;
			case 'boolean':
				return $value ? 'true' : 'false';
			case 'array':
			case 'object':
			case 'resource':
				throw new \InvalidArgumentException(
					'cannot serialize ' . gettype($value));
			default:
				return "'" . addcslashes($value, "'") . "'";
		}
	}
	
	private function nextUniqueIdentifier($baseName) {
		$num = @$this->identifiers[$baseName];
		if ($num == null) {
			$num = 0;
			$this->identifiers[$baseName] = 0;
		}
		else {
			$num = $this->identifiers[$baseName];		
		}
		$this->identifiers[$baseName]++;
		return "{$baseName}_$num";
	}
}