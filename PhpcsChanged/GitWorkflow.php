<?php

namespace PhpcsChanged\GitWorkflow;

use PhpcsChanged\NoChangesException;
use PhpcsChanged\ShellException;

function validateGitFileExists($gitFile, $git, callable $isReadable, callable $executeCommand, callable $debug) {
	if (! $isReadable($gitFile)) {
		throw new ShellException("Cannot read file '{$gitFile}'");
	}
	$gitStatusCommand = "${git} status --short " . escapeshellarg($gitFile);
	$debug('checking git existence of file with command:', $gitStatusCommand);
	$gitStatusOutput = $executeCommand($gitStatusCommand);
	$debug('git status output:', $gitStatusOutput);
	if (isset($gitStatusOutput[0]) && $gitStatusOutput[0] === '?') {
		throw new ShellException("File does not appear to be tracked by git: '{$gitFile}'");
	}
}

function getGitUnifiedDiff($gitFile, $git, callable $executeCommand, array $options, callable $debug) {
	$branchOption = isset($options['git-branch']) && ! empty($options['git-branch']) ? ' ' . escapeshellarg($options['git-branch']) . '...' : '';
	$stagedOption = empty( $branchOption ) && ! isset($options['git-unstaged']) ? ' --staged' : '';
	$unifiedDiffCommand = "{$git} diff{$stagedOption}{$branchOption} --no-prefix " . escapeshellarg($gitFile);
	$debug('running diff command:', $unifiedDiffCommand);
	$unifiedDiff = $executeCommand($unifiedDiffCommand);
	if (! $unifiedDiff) {
		throw new NoChangesException("Cannot get git diff for file '{$gitFile}'; skipping");
	}
	$debug('diff command output:', $unifiedDiff);
	return $unifiedDiff;
}

function isNewGitFile($gitFile, $git, callable $executeCommand, array $options, callable $debug) {
	if ( isset($options['git-branch']) && ! empty($options['git-branch']) ) {
		return isNewGitFileRemote( $gitFile, $git, $executeCommand, $options, $debug );
	} else {
		return isNewGitFileLocal( $gitFile, $git, $executeCommand, $options, $debug );
	}
}

function isNewGitFileRemote($gitFile, $git, callable $executeCommand, array $options, callable $debug) {
	$gitStatusCommand = "${git} cat-file -e " . escapeshellarg($options['git-branch']) . ':' . escapeshellarg($gitFile);
	$debug('checking status of file with command:', $gitStatusCommand);
	$return_val = 1;
	$gitStatusOutput = [];
	$gitStatusOutput = $executeCommand($gitStatusCommand, $gitStatusOutput, $return_val);
	$debug('status command output:', $gitStatusOutput);
	$debug('status command return val:', $return_val);
	return 0 !== $return_val;
}

function isNewGitFileLocal($gitFile, $git, callable $executeCommand, array $options, callable $debug) {
	$gitStatusCommand = "${git} status --short " . escapeshellarg($gitFile);
	$debug('checking git status of file with command:', $gitStatusCommand);
	$gitStatusOutput = $executeCommand($gitStatusCommand);
	$debug('git status output:', $gitStatusOutput);
	if (! $gitStatusOutput || false === strpos($gitStatusOutput, $gitFile)) {
		throw new ShellException("Cannot get git status for file '{$gitFile}'");
	}
	if (isset($gitStatusOutput[0]) && $gitStatusOutput[0] === '?') {
		throw new ShellException("File does not appear to be tracked by git: '{$gitFile}'");
	}
	return isset($gitStatusOutput[0]) && $gitStatusOutput[0] === 'A';
}

function getGitBasePhpcsOutput($gitFile, $git, $phpcs, $phpcsStandardOption, callable $executeCommand, array $options, callable $debug) {
	if ( isset($options['git-branch']) && ! empty($options['git-branch']) ) {
		$rev = escapeshellarg($options['git-branch']);
	} else {
		$rev = isset($options['git-unstaged']) ? ':0' : 'HEAD';
	}
	$oldFilePhpcsOutputCommand = "${git} show {$rev}:$(${git} ls-files --full-name " . escapeshellarg($gitFile) . ") | {$phpcs} --report=json -q" . $phpcsStandardOption . ' --stdin-path=' .  escapeshellarg($gitFile) . ' -';
	$debug('running orig phpcs command:', $oldFilePhpcsOutputCommand);
	$oldFilePhpcsOutput = $executeCommand($oldFilePhpcsOutputCommand);
	if (! $oldFilePhpcsOutput) {
		throw new ShellException("Cannot get old phpcs output for file '{$gitFile}'");
	}
	$debug('orig phpcs command output:', $oldFilePhpcsOutput);
	return $oldFilePhpcsOutput;
}

function getGitNewPhpcsOutput($gitFile, $phpcs, $cat, $phpcsStandardOption, callable $executeCommand, callable $debug) {
	$newFilePhpcsOutputCommand = "{$cat} " . escapeshellarg($gitFile) . " | {$phpcs} --report=json -q" . $phpcsStandardOption . ' --stdin-path=' .  escapeshellarg($gitFile) .' -';
	$debug('running new phpcs command:', $newFilePhpcsOutputCommand);
	$newFilePhpcsOutput = $executeCommand($newFilePhpcsOutputCommand);
	if (! $newFilePhpcsOutput) {
		throw new ShellException("Cannot get new phpcs output for file '{$gitFile}'");
	}
	$debug('new phpcs command output:', $newFilePhpcsOutput);
	if (false !== strpos($newFilePhpcsOutput, 'You must supply at least one file or directory to process')) {
		$debug('phpcs output implies file is empty');
		return '';
	}
	return $newFilePhpcsOutput;
}
