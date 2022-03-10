<?php

namespace PhpcsChanged;

use PhpcsChanged\DiffLineType;

class DiffLine {
	private $oldLine = null;
	private $newLine = null;
	private $type = null;
	private $line = null;

	public function __construct($oldLine, $newLine, DiffLineType $type, $line) {
		$this->type = $type;
		$this->line = $line;
		if (! $type->isAdd()) {
			$this->oldLine = $oldLine;
		}
		if (! $type->isRemove()) {
			$this->newLine = $newLine;
		}
	}

	public function getOldLineNumber() {
		return $this->oldLine;
	}

	public function getNewLineNumber() {
		return $this->newLine;
	}

	public function getType() {
		return $this->type;
	}

	public function getLine() {
		return $this->line;
	}

	public function __toString() {
		$oldLine = isset($this->oldLine) ? $this->oldLine : 'none';
		$newLine = isset($this->newLine) ? $this->newLine : 'none';
		$type = (string)$this->type;
		return "({$type}) {$oldLine} => {$newLine}: {$this->line}";
	}
}
