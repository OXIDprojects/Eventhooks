<?php

class event_hook1_0_1
{

    /**
     * {@inheritdoc}
     */
    public function up()
    {
        $oDb = oxDb::getDb();
         $sSql= 'INSERT INTO `oxuser` (`OXID`,`OXUSERNAME`) VALUES ("2","Testuser2");';
        $oDb->execute($sSql);
    }

    
    
    /**
     * {@inheritdoc}
     */
    public function down()
    {
    }
}