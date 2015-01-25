<?php

namespace Carrooi\Cloner\DI;

use Kdyby\Console\DI\ConsoleExtension;
use Nette\DI\CompilerExtension;
use Nette\DI\Config\Helpers;

/**
 *
 * @author David Kudera
 */
class ClonerExtension extends CompilerExtension
{


	/** @var array */
	private $defaults = [
		'debug' => '%debugMode%',
		'autoRun' => false,
		'paths' => [],
	];


	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);

		foreach ($this->compiler->getExtensions('Carrooi\Cloner\DI\IClonerPathsProvider') as $pathsProvider) {
			/** @var \Carrooi\Cloner\DI\IClonerPathsProvider $pathsProvider */

			$config['paths'] = Helpers::merge($config['paths'], $pathsProvider->getClonerPaths());
		}

		$cloner = $builder->addDefinition($this->prefix('cloner'))
			->setClass('Carrooi\Cloner\Cloner');

		foreach ($config['paths'] as $path) {
			$cloner->addSetup('addPath', [$path[0], $path[1]]);
		}

		$builder->addDefinition($this->prefix('autoRunner'))
			->setClass('Carrooi\Cloner\ClonerAutoRunner')
			->addSetup('setDebugMode', [$config['debug']])
			->addSetup('setAutoRun', [$config['autoRun']])
			->addTag('run');

		if ($this->compiler->getExtensions('Kdyby\Console\DI\ConsoleExtension') !== []) {
			$builder->addDefinition($this->prefix('command.run'))
				->setClass('Carrooi\Cloner\Commands\RunCommand')
				->addTag(ConsoleExtension::COMMAND_TAG);
		}
	}

}
