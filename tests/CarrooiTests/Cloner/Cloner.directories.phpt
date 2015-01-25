<?php

/**
 * Test: Carrooi\Cloner\Cloner
 *
 * @testCase CarrooiTests\Cloner\ClonerDirectoriesTest
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
class ClonerDirectoriesTest extends TestCase
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


	public function testGetFiles_directory()
	{
		$this->cloner->addPath(__DIR__. '/files/css/core', $this->public);
		$files = $this->cloner->getFiles();

		Assert::same([
			__DIR__. '/files/css/core/mixins.css' => $this->public. '/core/mixins.css',
			__DIR__. '/files/css/core/other/other.css' => $this->public. '/core/other/other.css',
			__DIR__. '/files/css/core/variables.css' => $this->public. '/core/variables.css',
		], $files);
	}


	public function testGetFiles_directories()
	{
		$this->cloner->addPath([
			__DIR__. '/files/css/components/widgets',
			__DIR__. '/files/js',
		], $this->public);
		$files = $this->cloner->getFiles();

		Assert::same([
			__DIR__. '/files/css/components/widgets/favorite.css' => $this->public. '/widgets/favorite.css',
			__DIR__. '/files/js/menu.js' => $this->public. '/js/menu.js',
			__DIR__. '/files/js/web.js' => $this->public. '/js/web.js',
		], $files);
	}

}


run(new ClonerDirectoriesTest);
