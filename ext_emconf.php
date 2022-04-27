<?php

/***************************************************************
 * Extension Manager/Repository config file for ext: "notifications"
 *
 * Manual updates:
 * Only the data in the array - anything else is removed by next write.
 * "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Notifications',
	'description' => 'Sends e-mail notifications on backend changes',
	'category' => 'misc',
	'author' => 'Philippe Greban',
	'author_email' => 'philippe.greban@gmail.com',
	'author_company' => '',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'version' => '2.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '9.5.0-10.5.99',
            'utility' => ''
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);