<?php

namespace PhpcsChanged;

use PhpcsChanged\DiffLine;
use PhpcsChanged\DiffLineType;

class DiffLineMap {
	private $diffLines = [];

	private function __construct(array $diffLines) {
		$this->diffLines = $diffLines;
	}

	public function getOldLineNumberForLine($lineNumber) {
		foreach ($this->diffLines as $diffLine) {
			if ($diffLine->getNewLineNumber() === $lineNumber) {
				return $diffLine->getOldLineNumber();
			}
		}
		// go through each changed line in the new file (each DiffLine of type context or add)
		// if the new line number is greater than the line number we are looking for
		// then add the last difference between the old and new lines to the line number we are looking for
		$lineNumberDelta = 0;
		$lastOldLine = 0;
		$lastNewLine = 0;
		foreach ($this->diffLines as $diffLine) {
			$lastOldLine = $diffLine->getOldLineNumber() ?: $lastOldLine;
			$lastNewLine = $diffLine->getNewLineNumber() ?: $lastNewLine;
			if ($diffLine->getType()->isRemove()) {
				continue;
			}
			if (($diffLine->getNewLineNumber() ?: 0) > $lineNumber) {
				return intval( $lineNumber + $lineNumberDelta );
			}
			$lineNumberDelta = ($diffLine->getOldLineNumber() ?: 0) - ($diffLine->getNewLineNumber() ?: 0);
		}
		return $lastOldLine + ($lineNumber - $lastNewLine);
	}

	public static function fromUnifiedDiff($unifiedDiff) {
		$diffStringLines = preg_split("/\r\n|\n|\r/", $unifiedDiff) ?: [];
		$oldStartLine = $newStartLine = null;
		$currentOldLine = $currentNewLine = null;
		$lines = [];
		foreach ($diffStringLines as $diffStringLine) {

			// Find the start of a hunk
			$matches = [];
			if (1 === preg_match('/^@@ \-(\d+),(\d+) \+(\d+),(\d+) @@/', $diffStringLine, $matches)) {
				$oldStartLine = isset($matches[1]) ? $matches[1] : NULL;
				$newStartLine = isset($matches[3]) ? $matches[3] : NULL;
				$currentOldLine = $oldStartLine;
				$currentNewLine = $newStartLine;
				continue;
			}

			// Ignore headers
			if (self::isLineDiffHeader($diffStringLine)) {
				continue;
			}

			// Parse a hunk
			if ($oldStartLine !== null && $newStartLine !== null) {
				$lines[] = new DiffLine((int)$currentOldLine, (int)$currentNewLine, self::getDiffLineTypeForLine($diffStringLine), $diffStringLine);
				if (self::isLineDiffRemoval($diffStringLine)) {
					$currentOldLine ++;
				} else if (self::isLineDiffAddition($diffStringLine)) {
					$currentNewLine ++;
				} else {
					$currentOldLine ++;
					$currentNewLine ++;
				}
			}
		}
		return new DiffLineMap($lines);
	}

	public static function getFileNameFromDiff($unifiedDiff) {
		$diffStringLines = preg_split("/\r\n|\n|\r/", $unifiedDiff) ?: [];
		foreach ($diffStringLines as $diffStringLine) {
			$matches = [];
			if (1 === preg_match('/^\-\-\- (\S+)/', $diffStringLine, $matches)) {
				return isset($matches[1]) ? $matches[1] : NULL;
			}
		}
		return null;
	}

	private static function getDiffLineTypeForLine($line) {
		if (self::isLineDiffRemoval($line)) {
			return DiffLineType::makeRemove();
		} elseif (self::isLineDiffAddition($line)) {
			return DiffLineType::makeAdd();
		}
		return DiffLineType::makeContext();
	}

	private static function isLineDiffHeader($line) {
		return (1 === preg_match('/^Index: /', $line) || 1 === preg_match('/^====/', $line) || 1 === preg_match('/^\-\-\-/', $line) || 1 === preg_match('/^\+\+\+/', $line));
	}

	private static function isLineDiffRemoval($line) {
		return (1 === preg_match('/^\-/', $line));
	}

	private static function isLineDiffAddition($line) {
		return (1 === preg_match('/^\+/', $line));
	}
}
