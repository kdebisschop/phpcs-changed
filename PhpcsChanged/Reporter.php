<?php

namespace PhpcsChanged;

use PhpcsChanged\PhpcsMessages;

interface Reporter {
	public function getFormattedMessages(PhpcsMessages $messages, array $options);
	public function getExitCode(PhpcsMessages $messages);
}
