<?php

namespace Carrooi\Cloner\Commands;

use Carrooi\Cloner\Cloner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 * @author David Kudera
 */
class RunCommand extends Command
{


	/** @var \Carrooi\Cloner\Cloner */
	private $cloner;


	/**
	 * @param \Carrooi\Cloner\Cloner $cloner
	 */
	public function __construct(Cloner $cloner)
	{
		parent::__construct();

		$this->cloner = $cloner;
	}


	public function configure()
	{
		$this->setName('cloner:run')
			->setDescription('Update files configured with Carrooi/Cloner')
			->addOption('force', null, InputOption::VALUE_OPTIONAL, 'Perform update', false);
	}


	/**
	 * @param \Symfony\Component\Console\Input\InputInterface $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$force = $input->getOption('force');

		if ($force) {
			$this->cloner->onCopy[] = function($source, $target) use ($output) {
				$output->writeln('Copying <info>'. $source. '</info> to <comment>'. $target. '</comment>');
			};

			$this->cloner->onRemove[] = function($file) use ($output) {
				$output->writeln('Removing <error>'. $file. '</error>');
			};

			$this->cloner->onLeave[] = function($file) use ($output) {
				$output->writeln('Leaving <info>'. $file. '</info>');
			};

			$this->cloner->run();
		} else {
			$files = $this->cloner->getFiles();
			$rebuild = $this->cloner->getRebuildList();

			foreach ($rebuild['copy'] as $file) {
				$source = $file;
				$target = $files[$file];

				$output->writeln('Copy <info>'. $source. '</info> to <comment>'. $target. '</comment>');
			}

			foreach ($rebuild['remove'] as $file) {
				$output->writeln('Remove <error>'. $file. '</error>');
			}

			foreach ($rebuild['leave'] as $file) {
				$output->writeln('Leave <info>'. $file. '</info>');
			}
		}

		return 0;
	}

}
