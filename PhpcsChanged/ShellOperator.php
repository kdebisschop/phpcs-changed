<?php

namespace PhpcsChanged;

/**
 * Interface to perform file and shell operations
 */
interface ShellOperator {
	public function validateExecutableExists($name, $command);

	public function executeCommand($command, array &$output = null, &$return_val = null);

	public function isReadable($fileName);

	public function exitWithCode($code);

	public function printError($message);
}
