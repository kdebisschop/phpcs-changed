<?php

namespace PhpcsChanged;

class PhpcsMessage {
	private $line;
	private $file;
	private $type;
	private $otherProperties;

	public function __construct($line, $file = null, $type, array $otherProperties) {
		$this->line = $line;
		$this->file = $file;
		$this->type = $type;
		$this->otherProperties = $otherProperties;
	}

	public function getLineNumber() {
		return $this->line;
	}

	public function getFile() {
		return $this->file;
	}

	public function setFile($file) {
		$this->file = $file;
	}

	public function getType() {
		return $this->type;
	}

	public function getMessage() {
		return isset($this->otherProperties['message']) ? $this->otherProperties['message'] : '';
	}

	public function getSource() {
		return isset($this->otherProperties['source']) ? $this->otherProperties['source'] : '';
	}

	public function toPhpcsArray() {
		return array_merge([
			'line' => $this->line,
		], $this->otherProperties);
	}
}
