<?php

namespace PhpcsChanged;

use PhpcsChanged\DiffLineMap;
use PhpcsChanged\PhpcsMessages;
use PhpcsChanged\ShellException;

function getVersion() {
	return '2.5.0';
}

function getNewPhpcsMessages($unifiedDiff, PhpcsMessages $oldPhpcsMessages, PhpcsMessages $newPhpcsMessages) {
	$map = DiffLineMap::fromUnifiedDiff($unifiedDiff);
	$fileName = DiffLineMap::getFileNameFromDiff($unifiedDiff);
	return PhpcsMessages::fromPhpcsMessages(array_values(array_filter($newPhpcsMessages->getMessages(), function($newMessage) use ($oldPhpcsMessages, $map) {
		$lineNumber = $newMessage->getLineNumber();
		if (! $lineNumber) {
			return true;
		}
		$oldLineNumber = $map->getOldLineNumberForLine($lineNumber);
		$oldMessagesContainingOldLineNumber = array_values(array_filter($oldPhpcsMessages->getMessages(), function($oldMessage) use ($oldLineNumber) {
			return $oldMessage->getLineNumber() === $oldLineNumber;
		}));
		return ! (count($oldMessagesContainingOldLineNumber) > 0);
	})), $fileName);
}

function getNewPhpcsMessagesFromFiles($diffFile, $phpcsOldFile, $phpcsNewFile) {
	$unifiedDiff = file_get_contents($diffFile);
	$oldFilePhpcsOutput = file_get_contents($phpcsOldFile);
	$newFilePhpcsOutput = file_get_contents($phpcsNewFile);
	if (! $unifiedDiff || ! $oldFilePhpcsOutput || ! $newFilePhpcsOutput) {
		throw new ShellException('Cannot read input files.');
	}
	return getNewPhpcsMessages(
		$unifiedDiff,
		PhpcsMessages::fromPhpcsJson($oldFilePhpcsOutput),
		PhpcsMessages::fromPhpcsJson($newFilePhpcsOutput)
	);
}
