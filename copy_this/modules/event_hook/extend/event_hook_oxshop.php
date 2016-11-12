<?php
/**
 * v1.0
 */

class event_hook_oxshop extends event_hook_oxshop_parent
{
    /**
     * Name of the lockfile.
     *
     * @var string
     */
    protected $_sLockFileName = 'event_hook.yml';
    
    /**
     * (Re)generates shop views
     *
     * @param bool  $blMultishopInheritCategories config option blMultishopInherit_oxcategories
     * @param array $aMallInherit                 array of config options blMallInherit
     *
     * @return bool is all views generated successfully
     */
    public function generateViews($blMultishopInheritCategories = false, $aMallInherit = null)
    {
        if( $this->_checkForLockfile() ){
            $this->_writeToFile();
            return;
        }
        $this->_prepareViewsQueries();
        $blSuccess = $this->_runQueries();
      
        $this->_cleanInvalidViews();

        return $blSuccess;
    }
    
    protected function _checkForLockfile()
    {
        $lockdir = getShopBasePath();
        if( file_exists( $lockdir.$this->_sLockFileName ) )
        {
            return true;
        }
        return false;
    }
    
    protected function _writeToFile(){
        include_once getShopBasePath() . "Spyc.php";
        $ymlData = Spyc::YAMLLoad( getShopBasePath().$this->_sLockFileName );
        $ymlData['events']['generateViews'] = 'true';
        $yaml = Spyc::YAMLDump($ymlData, true, false, true);
        file_put_contents(getShopBasePath().$this->_sLockFileName, $yaml );
    }
}
