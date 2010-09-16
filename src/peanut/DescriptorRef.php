<?php

namespace peanut;

class DescriptorRef {
	private $id;
	function __construct($id) {
		$this->id = $id;
	}
	function getId() {
		return $this->id;
	}
}