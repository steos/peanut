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

class XmlContext extends Context {
	private $file;
	private $version;
	private $encoding;
	
	static function fromFile($file, $version = '1.0', $enc = 'UTF-8') {
		$dom = new \DOMDocument($version, $enc);
		$dom->load($file, self::LIBXML_FLAGS());
		return new self($dom);
	}
	
	static function fromString($xml, $version = '1.0', $enc = 'UTF-8') {
		$dom = new \DOMDocument($version, $enc);
		$dom->loadXML($xml, self::LIBXML_FLAGS());
		return new self($dom);
	}
	
	/* 
	 * workaround because PHP doesn't allow expressions as const values
	 */
	static private function LIBXML_FLAGS() {
		return LIBXML_NOBLANKS | LIBXML_COMPACT; 
	}
	
	function __construct(\DOMDocument $dom) {
		parent::__construct();
		$this->dom = $dom;
		$this->load();
	}
	
	private function load() {
		if ($this->dom->documentElement->tagName != 'peanuts') {
			throw new PeanutException("this doesn't look like a peanut context");
		} 
		$children = $this->dom->documentElement->childNodes;
		for ($i = 0; $i < $children->length; ++$i) {
			$child = $children->item($i);
			if ($child->tagName != 'peanut') {
				throw new PeanutException("unexpected tag \"$child->tagName\"");
			}
			$ds = $this->parsePeanutNode($child);
			$this->descriptors[$ds->getId()] = $ds;
		}
	}
	
	private function parsePeanutNode(\DOMElement $node) {
		if (!$class = $node->getAttribute('class')) {
			throw new PeanutException("missing required \"class\" attribute");
		}
		if (!$id = $node->getAttribute('id')) {
			$id = uniqid();
		}
		$type = $node->getAttribute('type');
		if (!$type || $type == 'singleton') {
			$type = Descriptor::TYPE_SINGLETON;
		}
		else if ($type == 'prototype') {
			$type = Descriptor::TYPE_PROTOTYPE;
		}
		$descriptor = new Descriptor($id, $class, $type);
		if ($node->hasAttribute('factory')) {
			$descriptor->setFactoryMethod($node->getAttribute('factory'));
		}
		$children = $node->childNodes;
		for ($i = 0; $i < $children->length; ++$i) {
			$this->parsePeanutChildNode($children->item($i), $descriptor);
		}
		return $descriptor;
	}
	
	private function parsePeanutChildNode(\DOMElement $node, Descriptor $ds) {
		switch ($node->tagName) {
			case 'param':
				$this->parseParamNode($node, $ds);
			break;
			case 'property':
				$this->parsePropertyNode($node, $ds);
			break;
			default:
				throw new PeanutException(
					"unexpected element \"$node->tagName\" inside peanut " . 
					"\"{$ds->getId()}\" of class \"{$ds->getClass()}\"");
		}
	}
	
	private function parseParamNode(\DOMElement $node, Descriptor $ds) {
		$value = $this->parseValueChildNode($node);
		$ds->addParam($value);
	}
	
	private function parsePropertyNode(\DOMElement $node, Descriptor $ds) {
		if (!$node->hasAttribute('name')) {
			throw new ParseException("missing attribute \"name\" in peanut " .
				"{$ds->getId()} of class {$ds->getClass()}");
		}
		$name = $node->getAttribute('name');
		$value = $this->parseValueChildNode($node);
		$ds->setProperty($name, $value);
	}
	
	private function parseValueChildNode(\DOMElement $node) {
		if ($node->childNodes->length == 0) {
			// short hand for value node
			return $this->parseValueNode($node);
		}
		$child = $node->childNodes->item(0);
		if ($child instanceof \DOMText) {
			return $child->nodeValue;
		}
		return $this->parseValueNode($child);
	}
	
	private function parseValueNode(\DOMElement $node) {
		switch ($node->tagName) {
			case 'value':
				return $this->parseScalarValueNode($node);
			case 'ref':
				return $this->parseRefNode($node);
			case 'list':
				return $this->parseListNode($node);
			case 'map':
				return $this->parseMapNode($node);
			default:
				throw new PeanutException("unknown tag \"$node->tagName\"");
		}
	}
	
	private function parseScalarValueNode(\DOMElement $node) {
		return $node->textContent;
	}
	
	private function parseRefNode(\DOMElement $node) {
		if (!$id = $node->getAttribute('id')) {
			throw new PeanutException("missing \"id\" attribute in ref node");
		}
		return new DescriptorRef($id);
	}
	
	private function parseListNode(\DOMElement $node) {
		$children = $node->childNodes;
		$list = array();
		for ($i = 0; $i < $children->length; ++$i) {
			$child = $children->item($i);
			$list[] = $this->parseValueNode($child);
		}
		return $list;
	}
	
	private function parseMapNode(\DOMElement $node) {
		$children = $node->childNodes;
		$map = array();
		for ($i = 0; $i < $children->length; ++$i) {
			$child = $children->item($i);
			if ($child->tagName != 'entry') {
				throw new PeanutException("unexpected tag \"$child->tagName\"");
			}
			list($key, $value) = $this->parseMapEntryNode($child);
			$map[$key] = $value;
		}
		return $map;
	}
	
	private function parseMapEntryNode(\DOMElement $node) {
		if (!$key = $node->getAttribute('key')) {
			throw new PeanutException("missing required attribute \"key\"");
		}
		$value = $this->parseValueChildNode($node);
		return array($key, $value);
	}
}
