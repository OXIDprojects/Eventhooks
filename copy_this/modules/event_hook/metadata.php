<?php
/**
 * Module information
 */
$aModule = array(
    'id'          => 'event_hook',
    'title'       => 'Event - Hook',
    'description' => '',
    'thumbnail'   => '',
    'version'     => '1.0.2',
    'author'      => 'Hackathon Nuernberg',
    'email'       => '',
    'url'         => '',
    'extend'      => array(
        'oxshop'      => 'event_hook/extend/event_hook_oxshop',
    ),
    'files'       => array(
        'event_hook_setup'      => 'event_hook/files/event_hook_setup.php',
        'RunMigration'          => 'event_hook/files/RunMigration.php',
    ),
    'templates'   => array(),
    'blocks'      => array(),
     'events'      => array(
        'onActivate' => 'event_hook_setup::onActivate',
    ),
);
