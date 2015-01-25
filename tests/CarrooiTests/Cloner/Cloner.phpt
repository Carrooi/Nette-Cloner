<?php

/**
 * Test: Carrooi\Cloner\Cloner
 *
 * @testCase CarrooiTests\Cloner\ClonerTest
 * @author David Kudera
 */

namespace CarrooiTests\Cloner;

use Carrooi\Cloner\Cloner;
use Nette\Caching\Cache;
use Nette\Caching\Storages\MemoryStorage;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class ClonerTest extends TestCase
{


	/** @var \Nette\Caching\Storages\MemoryStorage */
	private $storage;

	/** @var \Carrooi\Cloner\Cloner */
	private $cloner;

	/** @var string */
	private $public;


	public function setUp()
	{
		@mkdir(TEMP_DIR. '/public');


		$this->public = realpath(TEMP_DIR. '/public');
		$this->storage = new MemoryStorage;
		$this->cloner = new Cloner($this->storage);
	}


	public function testGetRebuildList_newFile()
	{
		$this->cloner->addPath(__DIR__. '/files/css/style.css', $this->public. '/style.css');
		$list = $this->cloner->getRebuildList();

		Assert::same([
			'copy' => [
				__DIR__. '/files/css/style.css',
			],
			'remove' => [],
			'leave' => [],
		], $list);
	}


	public function testGetRebuildList_overwriteFile()
	{
		$cache = new Cache($this->storage, Cloner::CACHE_NAMESPACE);
		$cache->save('files', [
			$this->public. '/style.css' => 'thereShouldBeFileHash',
		]);

		$this->cloner->addPath(__DIR__. '/files/css/style.css', $this->public. '/style.css');
		$list = $this->cloner->getRebuildList();

		Assert::same([
			'copy' => [
				__DIR__. '/files/css/style.css',
			],
			'remove' => [],
			'leave' => [],
		], $list);
	}


	public function testGetRebuildList_remove()
	{
		$cache = new Cache($this->storage, Cloner::CACHE_NAMESPACE);
		$cache->save('files', [
			$this->public. '/style.css' => 'thereShouldBeFileHash',
		]);

		$list = $this->cloner->getRebuildList();

		Assert::same([
			'copy' => [],
			'remove' => [
				$this->public. '/style.css',
			],
			'leave' => [],
		], $list);
	}


	public function testGetRebuildList_same()
	{
		$cache = new Cache($this->storage, Cloner::CACHE_NAMESPACE);
		$cache->save('files', [
			$this->public. '/style.css' => hash_file('sha512', __DIR__. '/files/css/style.css'),
		]);

		$this->cloner->addPath(__DIR__. '/files/css/style.css', $this->public. '/style.css');

		$list = $this->cloner->getRebuildList();

		Assert::same([
			'copy' => [],
			'remove' => [],
			'leave' => [
				$this->public. '/style.css',
			],
		], $list);
	}


	public function testGetRebuildList()
	{
		$cache = new Cache($this->storage, Cloner::CACHE_NAMESPACE);
		$cache->save('files', [
			$this->public. '/other.css' => 'thereShouldBeFileHash',									// different hash
			$this->public. '/style.css' => hash_file('sha512', __DIR__. '/files/css/style.css'),	// same, leave
			$this->public. '/core/variables.css' => 'thereShouldBeFileHash',						// old file, remove
		]);

		$this->cloner->addPath(__DIR__. '/files/js/web.js', $this->public. '/web.js');				// new file
		$this->cloner->addPath(__DIR__. '/files/css/other.css', $this->public. '/other.css');
		$this->cloner->addPath(__DIR__. '/files/css/style.css', $this->public. '/style.css');

		$list = $this->cloner->getRebuildList();

		Assert::same([
			'copy' => [
				__DIR__. '/files/css/other.css',
				__DIR__. '/files/js/web.js',
			],
			'remove' => [
				$this->public. '/core/variables.css',
			],
			'leave' => [
				$this->public. '/style.css',
			],
		], $list);
	}


	public function testRun_newFile()
	{
		$cache = new Cache($this->storage, Cloner::CACHE_NAMESPACE);

		Assert::false(is_file($this->public. '/style.css'));

		$this->cloner->addPath(__DIR__. '/files/css/style.css', $this->public. '/style.css');
		$this->cloner->run();

		$files = $cache->load('files');

		Assert::true(isset($files[$this->public. '/style.css']));

		$hash = $files[$this->public. '/style.css'];

		Assert::same(hash_file('sha512', __DIR__. '/files/css/style.css'), $hash);
		Assert::true(is_file($this->public. '/style.css'));
	}


	public function testRun_changed()
	{
		$cache = new Cache($this->storage, Cloner::CACHE_NAMESPACE);

		file_put_contents($this->public. '/style.css', 'body {}');
		$cache->save('files', [
			$this->public. '/style.css' => hash_file('sha512', $this->public. '/style.css'),
		]);

		Assert::true(is_file($this->public. '/style.css'));

		$this->cloner->addPath(__DIR__. '/files/css/style.css', $this->public. '/style.css');
		$this->cloner->run();

		Assert::true(is_file($this->public. '/style.css'));
		Assert::notSame('body {}', file_get_contents($this->public. '/style.css'));
	}


	public function testRun_remove()
	{
		$cache = new Cache($this->storage, Cloner::CACHE_NAMESPACE);

		copy(__DIR__. '/files/css/style.css', $this->public. '/style.css');
		$cache->save('files', [
			$this->public. '/style.css' => hash_file('sha512', $this->public. '/style.css'),
		]);

		Assert::true(is_file($this->public. '/style.css'));

		$this->cloner->run();

		Assert::false(is_file($this->public. '/style.css'));
	}


	public function testRun_leave()
	{
		$cache = new Cache($this->storage, Cloner::CACHE_NAMESPACE);

		copy(__DIR__. '/files/css/style.css', $this->public. '/style.css');
		$cache->save('files', [
			$this->public. '/style.css' => hash_file('sha512', $this->public. '/style.css'),
		]);

		Assert::true(is_file($this->public. '/style.css'));

		$this->cloner->addPath(__DIR__. '/files/css/style.css', $this->public. '/style.css');
		$this->cloner->run();

		Assert::true(is_file($this->public. '/style.css'));
	}

}


run(new ClonerTest);
