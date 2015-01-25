<?php

/**
 * Test: Carrooi\Cloner\Cloner
 *
 * @testCase CarrooiTests\Cloner\ClonerFinderTest
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
class ClonerFinderTest extends TestCase
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


	public function testGetFiles_finderInvalid()
	{
		$this->cloner->addPath([
			'mask' => '*.css',
		], $this->public);

		Assert::exception(function() {
			$this->cloner->getFiles();
		}, 'Carrooi\Cloner\InvalidPathException', 'Missing "in" or "from" option in path.');
	}


	public function testGetFiles_finderIn()
	{
		$this->cloner->addPath([
			'mask' => '*.css',
			'in' => __DIR__. '/files/css/core',
		], $this->public);
		$files = $this->cloner->getFiles();

		Assert::same([
			__DIR__. '/files/css/core/mixins.css' => $this->public. '/core/mixins.css',
			__DIR__. '/files/css/core/variables.css' => $this->public. '/core/variables.css',
		], $files);
	}


	public function testGetFiles_finderFrom()
	{
		$this->cloner->addPath([
			'mask' => '*.css',
			'from' => __DIR__. '/files/css/components',
		], $this->public);
		$files = $this->cloner->getFiles();

		Assert::same([
			__DIR__. '/files/css/components/footer.css' => $this->public. '/components/footer.css',
			__DIR__. '/files/css/components/menu.css' => $this->public. '/components/menu.css',
			__DIR__. '/files/css/components/widgets/favorite.css' => $this->public. '/components/widgets/favorite.css',
		], $files);
	}


	public function testGetFiles_finders()
	{
		$this->cloner->addPath([
			[
				'mask' => '*.css',
				'in' => __DIR__. '/files/css/core',
			],
			[
				'mask' => '*.css',
				'from' => __DIR__. '/files/css/components',
			]

		], $this->public);
		$files = $this->cloner->getFiles();

		Assert::same([
			__DIR__. '/files/css/components/footer.css' => $this->public. '/components/footer.css',
			__DIR__. '/files/css/components/menu.css' => $this->public. '/components/menu.css',
			__DIR__. '/files/css/components/widgets/favorite.css' => $this->public. '/components/widgets/favorite.css',
			__DIR__. '/files/css/core/mixins.css' => $this->public. '/core/mixins.css',
			__DIR__. '/files/css/core/variables.css' => $this->public. '/core/variables.css',
		], $files);
	}

}


run(new ClonerFinderTest);
