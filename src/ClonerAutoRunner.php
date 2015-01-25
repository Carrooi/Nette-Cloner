<?php

namespace Carrooi\Cloner;

use Nette\Object;

/**
 *
 * @author David Kudera
 */
class ClonerAutoRunner extends Object
{


	/** @var \Carrooi\Cloner\Cloner */
	private $cloner;

	/** @var bool */
	private $debugMode = false;

	/** @var bool */
	private $autoRun = false;


	/**
	 * @param \Carrooi\Cloner\Cloner $cloner
	 */
	public function __construct(Cloner $cloner)
	{
		$this->cloner = $cloner;
	}


	/**
	 * @return bool
	 */
	public function isDebugMode()
	{
		return $this->debugMode === true;
	}


	/**
	 * @param bool $debugMode
	 * @return $this
	 */
	public function setDebugMode($debugMode = true)
	{
		$this->debugMode = $debugMode;
		return $this;
	}


	/**
	 * @return bool
	 */
	public function isAutoRun()
	{
		return $this->autoRun === true;
	}


	/**
	 * @param bool $autoRun
	 * @return $this
	 */
	public function setAutoRun($autoRun = true)
	{
		$this->autoRun = $autoRun;
		return $this;
	}


	public function run()
	{
		if (!$this->cloner->getCurrentFiles()) {
			$this->cloner->run();
		} elseif ($this->isDebugMode() && $this->isAutoRun()) {
			$this->cloner->run();
		}
	}

}
