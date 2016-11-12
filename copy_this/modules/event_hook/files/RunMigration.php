<?php

class RunMigration
{
    protected $_sFileVersion            = false;
    protected $_sCurrentVersion         = false;
    protected $_sModuleId               = false;
    
    
    public function startMigrations()
    {
        $oxModule = oxNew('oxModule');
        $this->_sModuleId = $this->getModuleIdByDir(__DIR__);
        $oxModule->load($this->_sModuleId);
        $this->_sFileVersion = str_replace(' ', '',$oxModule->getInfo('version'));
        $sModuleMigrationPath = $oxModule->getModuleFullPath() . "/migration";
        $aFilesAll = preg_grep('/^([^.])/', scandir($sModuleMigrationPath));
        sort($aFilesAll);
        foreach($aFilesAll as $sFile) {
            if ( $this->_needUpdate( $sFile)) {
                if (!$this->_upToDate()){
                    $sClass = str_replace('.php', '', $sFile);
                    require $sModuleMigrationPath.'/'.$sFile;
                    $oHook = new $sClass;
                    $oHook->up();
                    $this->_updateMigrateStatus();
                }
            }
        }
    }
    protected function _upToDate() {
        $oDb = oxDb::getDb(); 
        $sCheck =  'Select 1 from oxmigrationstatus where module_id ="'.$this->_sModuleId.'" and version ="'.$this->_sCurrentVersion.'";';
        try {
            $bResult = $oDb->getOne($sCheck);
        } catch (Exception $ex) {
            error_log("Error activating module: " . $ex->getMessage());
        }
        if ($bResult){
            return true;
        }
        return false;
    }
    
    protected function _needUpdate( $sFile) {
        
        $sVersion = str_replace('.php', '', $sFile);
        $sVersion= str_replace($this->_sModuleId, '', $sVersion );
        $this->_sCurrentVersion = str_replace('_', '.',$sVersion);
        $aVersion = explode('_' ,$sVersion);
        $aActiveVersion = explode('.' , $this->_sFileVersion);
        $sVersion = implode($aVersion);
        $sActiveVersion = implode($aActiveVersion);
        if ($sActiveVersion >= $sVersion) {
            return true;
        }
        return false;
    }
    
    public function getModuleIdByDir($psCurrentDir, $piStepsUpMax = 12) {
        if (is_string($psCurrentDir) && trim($psCurrentDir != '')) {

            // Initial values for search
            $blFound = false;
            $iCount = 0;
            $sSuffixDirUp = '/..';

            while (!$blFound = file_exists($psCurrentDir."/metadata.php") && $iCount < $piStepsUpMax) {
                $iCount++;
                $psCurrentDir .= $sSuffixDirUp ;
                $psCurrentDir = realpath($psCurrentDir);
            }

            if ($blFound) {
                $oxModule = oxNew('oxModule');
                $oxModuleList = oxNew('oxModuleList');
                $aModuleIds = $oxModuleList->getModuleIds();
                $aModulePaths = array();
                foreach ($aModuleIds as $sModuleId) {
                    $oxModule->load($sModuleId);
                    if ($oxModule->isActive()) {
                        $sPath = $oxModule->getModuleFullPath();
                        if (is_string($sPath) && trim($sPath) != '') {
                            $aModulePaths[$sPath] = $sModuleId;
                        }
                    }
                }
                if (isset($aModulePaths[$psCurrentDir])) {
                    return $aModulePaths[$psCurrentDir];
                }

            }
        }
        return false;
    }
    
    protected function _updateMigrateStatus() {
        $oDb = oxDb::getDb();
        $myUtilsObject = oxUtilsObject::getInstance();
        $sOxid = $myUtilsObject->generateUId();
        $sInsert =  'INSERT INTO `oxmigrationstatus` (`oxid`, `module_id`, `version`) VALUES ("'.$sOxid.'","'.$this->_sModuleId.'","'.$this->_sCurrentVersion.'");';
        try {
            $oDb->execute($sInsert);
        } catch (Exception $ex) {
            error_log("Error activating module: " . $ex->getMessage());
        }
    }
}