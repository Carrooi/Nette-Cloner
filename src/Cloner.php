<?php

namespace Carrooi\Cloner;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Object;
use Nette\Utils\Finder;

/**
 *
 * @author David Kudera
 */
class Cloner extends Object
{


	const CACHE_NAMESPACE = 'carrooi.cloner';


	/** @var \Nette\Caching\Cache */
	private $cache;

	/** @var array */
	private $paths = [];

	/** @var array */
	private $files = [];

	/** @var array */
	private $hashes = [];

	/** @var array */
	private $rebuild = null;


	/** @var callable[] */
	public $onCopy = [];

	/** @var callable[] */
	public $onRemove = [];

	/** @var callable[] */
	public $onLeave = [];


	/**
	 * @param \Nette\Caching\IStorage $storage
	 */
	public function __construct(IStorage $storage)
	{
		$this->cache = new Cache($storage, self::CACHE_NAMESPACE);
	}


	/**
	 * @internal
	 * @return \Nette\Caching\Cache
	 */
	public function getCache()
	{
		return $this->cache;
	}


	/**
	 * @param string|array $source
	 * @param string|array $target
	 * @return $this
	 */
	public function addPath($source, $target)
	{
		$this->invalidate();

		$this->paths[] = [
			'source' => $source,
			'target' => $target,
		];

		return $this;
	}


	/**
	 * @return array
	 */
	public function getPaths()
	{
		return $this->paths;
	}


	/**
	 * @return $this
	 */
	public function invalidate()
	{
		$this->files = $this->hashes = $this->rebuild;
		$this->rebuild = null;
		return $this;
	}


	/**
	 * @return array
	 */
	public function getFiles()
	{
		if (empty($this->files) && !empty($this->paths)) {
			foreach ($this->paths as $path) {
				if (is_array($path['source'])) {
					if (isset($path['source']['mask'])) {
						$this->processPath($path['source'], $path['target']);
					} else {
						foreach ($path['source'] as $source) {
							$this->processPath($source, $path['target']);
						}
					}
				} else {
					$this->processPath($path['source'], $path['target']);
				}
			}

			ksort($this->files);
			ksort($this->hashes);
		}

		return $this->files;
	}


	/**
	 * @param string $source
	 * @param string $target
	 * @param string $hash
	 */
	private function storeFile($source, $target, $hash)
	{
		$this->files[$source] = $target;
		$this->hashes[$target] = $hash;
	}


	/**
	 * @param string $source
	 * @param string $target
	 */
	private function processPath($source, $target)
	{
		if (is_array($source) && isset($source[0], $source[0]['mask'])) {
			foreach ($source as $subSource) {
				$this->processPath($subSource, $target);
			}
		} elseif ((is_array($source) && isset($source['mask'])) || is_dir($source)) {
			$mask = '*';
			$method = 'from';

			if (is_array($source)) {
				if (!isset($source['in']) && !isset($source['from'])) {
					throw new InvalidPathException('Missing "in" or "from" option in path.');
				}

				$mask = $source['mask'];

				if (isset($source['in'])) {
					$method = 'in';
					$source = $source['in'];
				} else {
					$source = $source['from'];
				}
			}

			foreach (Finder::findFiles($mask)->$method($source) as $fileName => $file) {
				$this->storeFile($fileName, $this->parseTargetFileName($fileName, $target, $source), $this->getFileHash($fileName));
			}
		} else if (is_file($source)) {
			$this->storeFile($source, $this->parseTargetFileName($source, $target), $this->getFileHash($source));
		} else {
			throw new InvalidPathException('Could not process '. $source. ' path.');
		}
	}


	/**
	 * @param string $source
	 * @param string $target
	 * @param string $sourceDirectory
	 * @return string
	 */
	private function parseTargetFileName($source, $target, $sourceDirectory = null)
	{
		if (!is_dir($target)) {
			return $target;
		}

		if (!$sourceDirectory) {
			return $target. DIRECTORY_SEPARATOR. pathinfo($source, PATHINFO_BASENAME);
		}

		$name = substr($source, strlen($sourceDirectory) + 1);
		return $target. DIRECTORY_SEPARATOR. pathinfo($sourceDirectory, PATHINFO_BASENAME). DIRECTORY_SEPARATOR. $name;
	}


	/**
	 * @param string $file
	 * @return string
	 */
	private function getFileHash($file)
	{
		return hash_file('sha512', $file);
	}


	/**
	 * @return array
	 */
	public function getCurrentFiles()
	{
		return $this->cache->load('files');
	}


	/**
	 * @return array
	 */
	public function getRebuildList()
	{
		if (!$this->rebuild) {
			$files = $this->getFiles();
			$oldFiles = $this->cache->load('files', function() {
				return [];
			});

			$this->rebuild = [
				'copy' => [],
				'remove' => [],
				'leave' => [],
			];

			foreach ($files as $source => $target) {
				if (!isset($oldFiles[$target])) {
					$this->rebuild['copy'][] = $source;
				} elseif ($this->hashes[$target] !== $oldFiles[$target]) {
					unset($oldFiles[$target]);
					$this->rebuild['copy'][] = $source;
				} else {
					unset($oldFiles[$target]);
					$this->rebuild['leave'][] = $target;
				}
			}

			foreach ($oldFiles as $target => $hash) {
				$this->rebuild['remove'][] = $target;
			}
		}

		return $this->rebuild;
	}


	public function run()
	{
		$rebuild = $this->getRebuildList();

		foreach ($rebuild['copy'] as $file) {
			$source = $file;
			$target = $this->files[$file];

			$directory = pathinfo($target, PATHINFO_DIRNAME);
			if (!is_dir($directory)) {
				mkdir($directory, 0777, true);
			}

			$this->onCopy($source, $target);
			copy($source, $target);
		}

		foreach ($rebuild['remove'] as $file) {
			$this->onRemove($file);
			@unlink($file);
		}

		foreach ($rebuild['leave'] as $file) {
			$this->onLeave($file);
		}

		$this->cache->save('files', $this->hashes);
	}

}
