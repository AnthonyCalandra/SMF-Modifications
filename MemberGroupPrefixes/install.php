<?php

/**
 * @package Member Group Prefix mod
 * @version 1.1
 * @author Suki <missallsunday@simplemachines.org>
 * @copyright 2012 Suki
 * @license http://www.mozilla.org/MPL/ MPL 1.1
 */

/*
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Initial Developer of the Original Code is
 * Anthony`/Project Evolution.
 * Portions created by the Initial Developer are Copyright (C)
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 * Jessica González <missallsunday@simplemachines.org>
 *
 */

	if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
		require_once(dirname(__FILE__) . '/SSI.php');

	elseif (!defined('SMF'))
		exit('<b>Error:</b> Cannot install - please verify you put this in the same place as SMF\'s index.php.');

	if (!defined('SMF'))
		die('Hacking attempt...');

	global $smcFunc;

	db_extend('packages');

	$smcFunc['db_add_column']('{db_prefix}membergroups',
		array(
			'name' => 'group_prefix', 'type' => 'tinytext', 'null' => 'not null',
		), '', 'ignore', '');