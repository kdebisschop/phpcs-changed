<?php

namespace PhpcsChanged;

class DiffLineType {
	private $type;

	private function __construct($type) {
		$this->type = $type;
	}

	public static function makeAdd() {
		return new self('add');
	}

	public static function makeRemove() {
		return new self('remove');
	}

	public static function makeContext() {
		return new self('context');
	}

	public function isAdd() {
		return $this->type === 'add';
	}

	public function isRemove() {
		return $this->type === 'remove';
	}

	public function isContext() {
		return $this->type === 'context';
	}

	public function __toString() {
		return $this->type;
	}
}
