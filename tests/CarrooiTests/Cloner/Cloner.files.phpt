<?php

/**
 * Test: Carrooi\Cloner\Cloner
 *
 * @testCase CarrooiTests\Cloner\ClonerFilesTest
 * @author David Kudera
 */

namespace CarrooiTests\Cloner;

use Carrooi\Cloner\Cloner;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class ClonerFilesTest extends TestCase
{


	/** @var \Carrooi\Cloner\Cloner */
	private $cloner;

	/** @var string */
	private $public;


	public function setUp()
	{
		@mkdir(TEMP_DIR. '/public');

		$this->public = realpath(TEMP_DIR. '/public');
		$this->cloner = new Cloner(new MemoryStorage);
	}


	public function testGetFiles_file()
	{
		$this->cloner->addPath(__DIR__. '/files/css/style.css', $this->public. '/style.css');
		$files = $this->cloner->getFiles();

		Assert::same([
			__DIR__. '/files/css/style.css' => $this->public. '/style.css',
		], $files);
	}


	public function testGetFiles_fileToDirectory()
	{
		$this->cloner->addPath(__DIR__. '/files/css/core/variables.css', $this->public);
		$files = $this->cloner->getFiles();

		Assert::same([
			__DIR__. '/files/css/core/variables.css' => $this->public. '/variables.css',
		], $files);
	}


	public function testGetFiles_filesToDirectory()
	{
		$this->cloner->addPath([
			__DIR__. '/files/css/style.css',
			__DIR__. '/files/js/web.js',
		], $this->public);
		$files = $this->cloner->getFiles();

		Assert::same([
			__DIR__. '/files/css/style.css' => $this->public. '/style.css',
			__DIR__. '/files/js/web.js' => $this->public. '/web.js',
		], $files);
	}

}


run(new ClonerFilesTest);
