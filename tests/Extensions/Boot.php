<?php

namespace tests\Extensions;

use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\BeforeFirstTestHook;

class Boot implements BeforeFirstTestHook, AfterLastTestHook
{
	public function executeBeforeFirstTest(): void
	{
		// phpunit --testsuite Unit
		echo sprintf("testsuite: %s\n", $this->getPhpUnitParam("testsuite"));

		// phpunit --filter CreateCompanyTest
		echo sprintf("filter: %s\n", $this->getPhpUnitParam("filter"));

		\GCMI_Activator::activate( false );
	}

	public function executeAfterLastTest(): void
	{
	    \GCMI_Activator::deactivate( false );
	}

	/**
	 * @return string|null
	 */
	protected function getPhpUnitParam(string $paramName): ?string
	{
		global $argv;
		$k = array_search("--$paramName", $argv);
		if (!$k) return null;
		return $argv[$k + 1];
	}
}