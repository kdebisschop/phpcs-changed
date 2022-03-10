<?php

namespace PhpcsChanged\SvnWorkflow;

use PhpcsChanged\NoChangesException;
use PhpcsChanged\ShellException;

function validateSvnFileExists($svnFile, $svn, callable $isReadable, callable $executeCommand, callable $debug) {
	if (! $isReadable($svnFile)) {
		throw new ShellException("Cannot read file '{$svnFile}'");
	}
	$svnStatusCommand = "${svn} info " . escapeshellarg($svnFile);
	$debug('checking svn existence of file with command:', $svnStatusCommand);
	$svnStatusOutput = $executeCommand($svnStatusCommand);
	$debug('svn status output:', $svnStatusOutput);
	if (! $svnStatusOutput || false === strpos($svnStatusOutput, 'Schedule:')) {
		throw new ShellException("Cannot get svn existence info for file '{$svnFile}'");
	}
}

function getSvnUnifiedDiff($svnFile, $svn, callable $executeCommand, callable $debug) {
	$unifiedDiffCommand = "{$svn} diff " . escapeshellarg($svnFile);
	$debug('running diff command:', $unifiedDiffCommand);
	$unifiedDiff = $executeCommand($unifiedDiffCommand);
	if (! $unifiedDiff) {
		throw new NoChangesException("Cannot get svn diff for file '{$svnFile}'; skipping");
	}
	$debug('diff command output:', $unifiedDiff);
	return $unifiedDiff;
}

function isNewSvnFile($svnFile, $svn, callable $executeCommand, callable $debug) {
	$svnStatusCommand = "${svn} info " . escapeshellarg($svnFile);
	$debug('checking svn status of file with command:', $svnStatusCommand);
	$svnStatusOutput = $executeCommand($svnStatusCommand);
	$debug('svn status output:', $svnStatusOutput);
	if (! $svnStatusOutput || false === strpos($svnStatusOutput, 'Schedule:')) {
		throw new ShellException("Cannot get svn info for file '{$svnFile}'");
	}
	return (false !== strpos($svnStatusOutput, 'Schedule: add'));
}

function getSvnBasePhpcsOutput($svnFile, $svn, $phpcs, $phpcsStandardOption, callable $executeCommand, callable $debug) {
	$oldFilePhpcsOutputCommand = "${svn} cat " . escapeshellarg($svnFile) . " | {$phpcs} --report=json -q" . $phpcsStandardOption . ' --stdin-path=' .  escapeshellarg($svnFile) . ' -';
	$debug('running orig phpcs command:', $oldFilePhpcsOutputCommand);
	$oldFilePhpcsOutput = $executeCommand($oldFilePhpcsOutputCommand);
	if (! $oldFilePhpcsOutput) {
		throw new ShellException("Cannot get old phpcs output for file '{$svnFile}'");
	}
	$debug('orig phpcs command output:', $oldFilePhpcsOutput);
	return $oldFilePhpcsOutput;
}

function getSvnNewPhpcsOutput($svnFile, $phpcs, $cat, $phpcsStandardOption, callable $executeCommand, callable $debug) {
	$newFilePhpcsOutputCommand = "{$cat} " . escapeshellarg($svnFile) . " | {$phpcs} --report=json -q" . $phpcsStandardOption . ' --stdin-path=' .  escapeshellarg($svnFile) . ' -';
	$debug('running new phpcs command:', $newFilePhpcsOutputCommand);
	$newFilePhpcsOutput = $executeCommand($newFilePhpcsOutputCommand);
	if (! $newFilePhpcsOutput) {
		throw new ShellException("Cannot get new phpcs output for file '{$svnFile}'");
	}
	$debug('new phpcs command output:', $newFilePhpcsOutput);
	if (false !== strpos($newFilePhpcsOutput, 'You must supply at least one file or directory to process')) {
		$debug('phpcs output implies file is empty');
		return '';
	}
	return $newFilePhpcsOutput;
}
