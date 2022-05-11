<?php
/**
 * Copyright (C) 2022 Magentix SARL
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

class Magentix_Axepta_Block_Form extends Mage_Payment_Block_Form
{
    /**
     * Set form template
     *
     * @return void
     */
	protected function _construct()
	{
		$this->setTemplate('axepta/form.phtml');

		parent::_construct();
	}
}
