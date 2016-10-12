//<?php

class cms_hook_Database extends _HOOK_CLASS_
{


	/**
	 * Constructor
	 * Gets stores which are always needed to save individual queries
	 *
	 */
	public function __construct()
	{
		$this->initLoad[] = 'cms_menu';

		/* Hand over to normal method */
		parent::__construct();
	}

}