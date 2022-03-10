<?php

namespace PhpcsChanged;

use PhpcsChanged\ShellOperator;
use function PhpcsChanged\Cli\printError;

/**
 * Module to perform file and shell operations
 */
class UnixShell implements ShellOperator {
	public function validateExecutableExists($name, $command) {
		exec(sprintf("type %s > /dev/null 2>&1", escapeshellarg($command)), $ignore, $returnVal);
		if ($returnVal != 0) {
			throw new \Exception("Cannot find executable for {$name}, currently set to '{$command}'.");
		}
	}

	public function executeCommand($command, array &$output = null, &$return_val = null) {
		exec($command, $output, $return_val) ?: '';
		return join(PHP_EOL, $output) . PHP_EOL;
	}

	public function isReadable($fileName) {
		return is_readable($fileName);
	}

	public function exitWithCode($code) {
		exit($code);
	}

	public function printError($output) {
		printError($output);
	}
}
