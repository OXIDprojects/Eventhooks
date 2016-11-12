<?php

/**
 * This software is the property of shoptimax GmbH and is protected.
 * @copyright (c) shoptimax GmbH | 2016
 */

class event_hook_setup extends oxSuperCfg
{

    public static function onActivate()
    {
        $oDb = oxDb::getDb(); 
        $createSql = '
            CREATE TABLE IF NOT EXISTS `oxmigrationstatus` (
                `OXID` char(32) NOT NULL PRIMARY KEY,
                `module_id` varchar(255) NOT NULL,
                `version` varchar(255) NOT NULL UNIQUE,
                `executed` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT=\'Stores the migrationstatus\';
        ';
        
        try {
            $oDb->execute($createSql);
        } catch (Exception $ex) {
            error_log("Error activating module: " . $ex->getMessage());
        }
        $RunMigration = oxNew('RunMigration');
        $RunMigration->startMigrations();
    }
}