<?php

/**
 * Test: Carrooi\Cloner\Commands\RunCommand
 *
 * @testCase CarrooiTests\Cloner\Commands\RunCommand
 * @author David Kudera
 */

namespace CarrooiTests\Cloner\Commands;

use Kdyby\Console\StringOutput;
use Nette\Configurator;
use Symfony\Component\Console\Input\ArrayInput;
use Tester\Assert;
use Tester\FileMock;
use Tester\TestCase;

require_once __DIR__. '/../../bootstrap.php';

/**
 *
 * @author David Kudera
 */
class RunCommandTest extends TestCase
{


	/** @var \Kdyby\Console\Application */
	private $application;

	/** @var \Carrooi\Cloner\Cloner */
	private $cloner;

	/** @var \Carrooi\Cloner\Commands\RunCommand */
	private $command;


	public function setUp()
	{
		@mkdir(TEMP_DIR. '/public');

		$config = new Configurator;
		$config->setTempDirectory(TEMP_DIR);
		$config->addParameters(['appDir' => __DIR__. '/../']);
		$config->addConfig(__DIR__. '/../config/config.neon');

		$context = $config->createContainer();

		$this->application = $context->getByType('Kdyby\Console\Application');
		$this->cloner = $context->getByType('Carrooi\Cloner\Cloner');
		$this->command = $this->application->find('cloner:run');
	}


	public function testExecute()
	{
		$original = [
			'style' => FileMock::create('', 'css'),
			'other' => FileMock::create('', 'css'),
			'web' => FileMock::create('new', 'js'),
		];
		$temp = [
			'style' => FileMock::create('', 'css'),
			'other' => FileMock::create('', 'css'),
			'web' => FileMock::create('old', 'js'),
			'menu' => FileMock::create('', 'css'),
		];

		$this->cloner->addPath($original['style'], $temp['style']);
		$this->cloner->addPath($original['other'], $temp['other']);
		$this->cloner->addPath($original['web'], $temp['web']);

		$this->cloner->getCache()->save('files', [
			$temp['style'] => filemtime($temp['style']),
			$temp['other'] => 555,
			$temp['menu'] => 555,
		]);

		$input = new ArrayInput([
			'command' => 'cloner:run',
		]);
		$output = new StringOutput;

		$this->command->run($input, $output);

		$output = explode("\n", $output->getOutput());
		Assert::same([
			'Copy '. $original['other']. ' to '. $temp['other'],
			'Copy '. $original['web']. ' to '. $temp['web'],
			'Remove '. $temp['menu'],
			'Leave '. $temp['style'],
			'',
		], $output);

		Assert::same('old', file_get_contents($temp['web']));
	}

}


run(new RunCommandTest);
