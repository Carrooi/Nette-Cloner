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
		$config->addConfig(__DIR__. '/../config/simple.neon');

		$context = $config->createContainer();

		$this->application = $context->getByType('Kdyby\Console\Application');
		$this->cloner = $context->getByType('Carrooi\Cloner\Cloner');
		$this->command = $this->application->find('cloner:run');
	}


	public function testExecute()
	{
		copy(__DIR__. '/../files/css/style.css', TEMP_DIR. '/public/style.css');
		copy(__DIR__. '/../files/css/other.css', TEMP_DIR. '/public/other.css');
		copy(__DIR__. '/../files/js/menu.js', TEMP_DIR. '/public/menu.js');

		$this->cloner->getCache()->save('files', [
			TEMP_DIR. '/public/style.css' => hash_file('sha512', TEMP_DIR. '/public/style.css'),
			TEMP_DIR. '/public/other.css' => 'thereShouldBeFileHash',
			TEMP_DIR. '/public/menu.js' => 'doesNotMatter',
		]);

		$input = new ArrayInput([
			'command' => 'cloner:run',
		]);
		$output = new StringOutput;

		$this->command->run($input, $output);

		$output = explode("\n", $output->getOutput());
		Assert::same([
			'Copy '. __DIR__. '/..//files/css/other.css to '. TEMP_DIR. '/public/other.css',
			'Copy '. __DIR__. '/..//files/js/web.js to '. TEMP_DIR. '/public/web.js',
			'Remove '. TEMP_DIR. '/public/menu.js',
			'Leave '. TEMP_DIR. '/public/style.css',
			'',
		], $output);

		Assert::false(is_file(TEMP_DIR. '/public/web.js'));
	}


	public function testExecute_force()
	{
		copy(__DIR__. '/../files/css/style.css', TEMP_DIR. '/public/style.css');
		copy(__DIR__. '/../files/css/other.css', TEMP_DIR. '/public/other.css');
		copy(__DIR__. '/../files/js/menu.js', TEMP_DIR. '/public/menu.js');

		$this->cloner->getCache()->save('files', [
			TEMP_DIR. '/public/style.css' => hash_file('sha512', TEMP_DIR. '/public/style.css'),
			TEMP_DIR. '/public/other.css' => 'thereShouldBeFileHash',
			TEMP_DIR. '/public/menu.js' => 'doesNotMatter',
		]);

		$input = new ArrayInput([
			'command' => 'cloner:run',
			'--force' => true,
		]);
		$output = new StringOutput;

		Assert::true(is_file(TEMP_DIR. '/public/other.css'));
		Assert::false(is_file(TEMP_DIR. '/public/web.js'));
		Assert::true(is_file(TEMP_DIR. '/public/menu.js'));
		Assert::true(is_file(TEMP_DIR. '/public/style.css'));

		$this->command->run($input, $output);

		$output = explode("\n", $output->getOutput());
		Assert::same([
			'Copying '. __DIR__. '/..//files/css/other.css to '. TEMP_DIR. '/public/other.css',
			'Copying '. __DIR__. '/..//files/js/web.js to '. TEMP_DIR. '/public/web.js',
			'Removing '. TEMP_DIR. '/public/menu.js',
			'Leaving '. TEMP_DIR. '/public/style.css',
			'',
		], $output);

		Assert::true(is_file(TEMP_DIR. '/public/other.css'));
		Assert::true(is_file(TEMP_DIR. '/public/web.js'));
		Assert::false(is_file(TEMP_DIR. '/public/menu.js'));
		Assert::true(is_file(TEMP_DIR. '/public/style.css'));
	}

}


run(new RunCommandTest);
