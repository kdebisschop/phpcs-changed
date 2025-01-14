<?php
declare(strict_types=1);

namespace PhpcsChanged;

use PhpcsChanged\Reporter;
use PhpcsChanged\PhpcsMessages;
use PhpcsChanged\PhpcsMessagesHelpers;
use PhpcsChanged\LintMessage;

class JsonReporter implements Reporter {
	public function getFormattedMessages(PhpcsMessages $messages, array $options): string { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$files = array_unique(array_map(function(LintMessage $message): string {
			return $message->getFile() ?? 'STDIN';
		}, $messages->getMessages()));
		if (empty($files)) {
			$files = ['STDIN'];
		}

		$outputByFile = array_map(function(string $file) use ($messages): array {
			$messagesForFile = array_values(array_filter($messages->getMessages(), function(LintMessage $message) use ($file): bool {
				return ($message->getFile() ?? 'STDIN') === $file;
			}));
			return $this->getFormattedMessagesForFile($messagesForFile, $file);
		}, $files);

		$errors = array_values(array_filter($messages->getMessages(), function($message) {
			return $message->getType() === 'ERROR';
		}));
		$warnings = array_values(array_filter($messages->getMessages(), function($message) {
			return $message->getType() === 'WARNING';
		}));
		$messages = array_map(function($message) {
			return PhpcsMessagesHelpers::messageToPhpcsArray($message);
		}, $messages->getMessages());
		$dataForJson = [
			'totals' => [
				'errors' => count($errors),
				'warnings' => count($warnings),
				'fixable' => 0,
			],
			'files' => array_merge(...$outputByFile),
		];
		$output = json_encode($dataForJson, JSON_UNESCAPED_SLASHES);
		if (! $output) {
			throw new \Exception('Failed to JSON-encode result messages');
		}
		return $output;
	}

	private function getFormattedMessagesForFile(array $messages, string $file): array {
		$errors = array_values(array_filter($messages, function($message) {
			return $message->getType() === 'ERROR';
		}));
		$warnings = array_values(array_filter($messages, function($message) {
			return $message->getType() === 'WARNING';
		}));
		$messageArrays = array_map(function(LintMessage $message): array {
			return PhpcsMessagesHelpers::messageToPhpcsArray($message);
		}, $messages);
		$dataForJson = [
				$file => [
					'errors' => count($errors),
					'warnings' => count($warnings),
					'messages' => $messageArrays,
				],
		];
		return $dataForJson;
	}

	public function getExitCode(PhpcsMessages $messages): int {
		return (count($messages->getMessages()) > 0) ? 1 : 0;
	}
}
