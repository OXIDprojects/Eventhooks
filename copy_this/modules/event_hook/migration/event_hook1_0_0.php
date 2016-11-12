<?php

class event_hook1_0_0
{

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $oDb = oxDb::getDb();
        $sSql= 'INSERT INTO `oxuser` (`OXID`,`OXUSERNAME`) VALUES ("1","Testuser");';
        $oDb->execute($sSql);
    }

    
    
    /**
     * {@inheritdoc}
     */
    public function down()
    {
    }
}