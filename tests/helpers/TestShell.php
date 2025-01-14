<?php
declare(strict_types=1);

namespace PhpcsChangedTests;

use PhpcsChanged\ShellOperator;

class TestShell implements ShellOperator {

	private $readableFileNames = [];

	private $commands = [];

	private $commandsCalled = [];

	private $fileHashes = [];

	public function __construct(array $readableFileNames) {
		foreach ($readableFileNames as $fileName) {
			$this->registerReadableFileName($fileName);
		}
	}

	public function registerReadableFileName(string $fileName, bool $override = false): bool {
		if (!isset($this->readableFileNames[$fileName]) || $override ) {
			$this->readableFileNames[$fileName] = true;
			return true;
		}
		throw new \Exception("Already registered file name: {$fileName}");
	}

	public function registerCommand(string $command, string $output, int $return_val = 0, bool $override = false): bool {
		if (!isset($this->commands[$command]) || $override) {
			$this->commands[$command] = [
				'output' => $output,
				'return_val' => $return_val,
			];
			return true;
		}
		throw new \Exception("Already registered command: {$command}");
	}

	public function deregisterCommand(string $command): bool {
		if (isset($this->commands[$command])) {
			unset($this->commands[$command]);
			return true;
		}
		throw new \Exception("No registered command: {$command}");
	}

	public function setFileHash(string $fileName, string $hash): void {
		$this->fileHashes[$fileName] = $hash;
	}

	public function isReadable(string $fileName): bool {
		return isset($this->readableFileNames[$fileName]);
	}

	public function exitWithCode(int $code): void {} // phpcs:ignore VariableAnalysis

	public function printError(string $message): void {} // phpcs:ignore VariableAnalysis

	public function validateExecutableExists(string $name, string $command): void {} // phpcs:ignore VariableAnalysis

	public function getFileHash(string $fileName): string {
		return $this->fileHashes[$fileName] ?? $fileName;
	}

	public function executeCommand(string $command, array &$output = null, int &$return_val = null): string {
		foreach ($this->commands as $registeredCommand => $return) {
			if ($registeredCommand === substr($command, 0, strlen($registeredCommand)) ) {
				$return_val = $return['return_val'];
				$output = $return['output'];
				$this->commandsCalled[$registeredCommand] = $command;
				return $return['output'];
			}
		}

		throw new \Exception("Unknown command: {$command}");
	}

	public function resetCommandsCalled(): void {
		$this->commandsCalled = [];
	}

	public function wasCommandCalled(string $registeredCommand): bool {
		return isset($this->commandsCalled[$registeredCommand]);
	}

	public function getFileNameFromPath(string $path): string {
		$parts = explode('/', $path);
		return end($parts);
	}
}
