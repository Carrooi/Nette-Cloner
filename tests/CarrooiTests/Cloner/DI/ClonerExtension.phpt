<?php

/**
 * Test: Carrooi\Cloner\DI\ClonerExtension
 *
 * @testCase CarrooiTests\Cloner\DI\ClonerExtensionTest
 * @author David Kudera
 */

namespace CarrooiTests\Cloner\DI;

use Carrooi\Cloner\DI\IClonerPathsProvider;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Tester\Assert;
use Tester\TestCase;

require_once __DIR__. '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class ClonerExtensionTest extends TestCase
{


	/** @var \Carrooi\Cloner\Cloner */
	private $cloner;

	/** @var \Carrooi\Cloner\ClonerAutoRunner */
	private $autoRunner;

	/** @var string */
	private $public;


	public function setUp()
	{
		@mkdir(TEMP_DIR. '/public');

		$config = new Configurator;
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['appDir' => __DIR__. '/../']);
		$config->addConfig(__DIR__. '/../config/config.neon');
		$config->addConfig(__DIR__. '/../config/cloner.neon');

		$config->onCompile[] = function(Configurator $config, Compiler $compiler) {
			$compiler->addExtension('testCloner', new TestClonerExtension);
		};

		$context = $config->createContainer();

		$this->cloner = $context->getByType('Carrooi\Cloner\Cloner');
		$this->autoRunner = $context->getByType('Carrooi\Cloner\ClonerAutoRunner');
		$this->public = TEMP_DIR. '/public';
	}


	public function testGetFiles()
	{
		$files = $this->cloner->getFiles();

		Assert::same([
			__DIR__. '/..//files/css/components/footer.css' => $this->public. '/components/footer.css',
			__DIR__. '/..//files/css/components/menu.css' => $this->public. '/components/menu.css',
			__DIR__. '/..//files/css/components/widgets/favorite.css' => $this->public. '/components/widgets/favorite.css',
			__DIR__. '/..//files/css/core/mixins.css' => $this->public. '/core/mixins.css',
			__DIR__. '/..//files/css/core/other/other.css' => $this->public. '/core/other/other.css',
			__DIR__. '/..//files/css/core/variables.css' => $this->public. '/core/variables.css',
			__DIR__. '/..//files/css/other.css' => $this->public. '/other.css',
			__DIR__. '/..//files/css/style.css' => $this->public. '/style.css',
			__DIR__. '/..//files/js/menu.js' => $this->public. '/js/menu.js',
			__DIR__. '/..//files/js/web.js' => $this->public. '/js/web.js',
			__DIR__. '/../files/templates/notification.html' => $this->public. '/notification.html',
		], $files);
	}


	public function testIsDebugMode()
	{
		Assert::true($this->autoRunner->isDebugMode());
	}


	public function testIsAutoRun()
	{
		Assert::true($this->autoRunner->isAutoRun());
	}

}


/**
 * @author David Kudera
 */
class TestClonerExtension extends CompilerExtension implements IClonerPathsProvider
{


	/**
	 * @return array
	 */
	public function getClonerPaths()
	{
		return [
			[__DIR__. '/../files/templates/notification.html', TEMP_DIR. '/public/notification.html'],
		];
	}

}


run(new ClonerExtensionTest);
