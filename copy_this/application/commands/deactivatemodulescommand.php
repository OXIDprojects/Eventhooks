<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Deactivate Modules command
 *
 * Clears out OXID cache from tmp folder
 */
class DeactivateModulesCommand extends oxConsoleCommand
{
    
    /**
     * @var array|null Available module ids
     */
    protected $_aAvailableModuleIds = null;
    
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('modules:deactivate');
        $this->setDescription('Deactivate OXID modules');
    }
    
    /**
     * {@inheritdoc}
     */
    public function help(oxIOutput $oOutput)
    {
        $oOutput->writeLn('Usage: modules:deactivate [options] <module_id> [<other_module_id>...]');
        $oOutput->writeLn();
        $oOutput->writeLn('This command deactivates OXID modules');
        $oOutput->writeln();
        $oOutput->writeLn('Available options (if no option all modules will be deactivated):');
        $oOutput->writeLn('  -c, --check   Checks yaml file which modules have to be deactivated');
        $oOutput->writeLn('  -s  --single  Only deactivates the named Module(s)');
        $oOutput->writeLn('  -a  --all    Deactivate all modules');
        $oOutput->writeLn('  -l  --list    list available module Ids');
    }
    
    /**
     * {@inheritdoc}
     */
    public function execute(oxIOutput $oOutput)
    {
        $oInput = $this->getInput();
        $blAll = !$oInput->hasOption(array('c', 'check', 's', 'single', 'a', 'all', 'l', 'list'));

        if ( $blAll || $oInput->hasOption(array('a', 'all')) ) {
            $this->_deactivateAll();
        }
        
        if ( $oInput->hasOption(array('c', 'check'))) {
            $aModuleIds = $this->_getList();
            foreach ( $aModuleIds as $sModuleId ){
                $this->_deactivateModule( $sModuleId );
            }
        }
        
        if ( $oInput->hasOption(array('s', 'single')) ) {
            try {
                $aModuleIds = $this->_parseModuleIds( $oInput );
            } catch (oxInputException $oEx) {
                $oOutput->writeLn($oEx->getMessage());
                return;
            }
            foreach( $aModuleIds as $sModuleId){
                $this->_deactivateModule( $sModuleId );
            }
        }
        
        if ( $oInput->hasOption(array('l', 'list'))) {
            $aModuleIds = $this->_getAvailableModuleIds();
            foreach ($aModuleIds as $sModuleId)
            {
                $sState = 'inactiv';
                if ($this->_checkStatus($sModuleId))
                {
                    $sState = 'active';
                }
                $oOutput->writeLn( $sModuleId . ' : ' . $sState);
            }
        }
    }
    
    /**
     * Checks if module is activated
     *
     * @return bool
     */
    protected function _checkStatus( $sModuleId )
    {
        $oModule = oxNew('oxmodule');
        if ($oModule->load($sModuleId))
        {
            if ( $oModule->isActive()) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Parse and return module ids from input
     *
     * @return array
     *
     * @throws oxInputException
     */
    protected function _parseModuleIds( $oInput )
    {
        if (count($oInput->getArguments()) < 2) { // Note: first argument is command name
            /** @var oxInputException $oEx */
            $oEx = oxNew('oxInputException');
            $oEx->setMessage('Please specify at least one module');
            throw $oEx;
        }

        $aModuleIds = $oInput->getArguments();
        array_shift($aModuleIds); // Getting rid of command name argument

        $aAvailableModuleIds = $this->_getAvailableModuleIds();

        // Checking if all provided module ids exist
        foreach ($aModuleIds as $sModuleId) {

            if (!in_array($sModuleId, $aAvailableModuleIds)) {
                /** @var oxInputException $oEx */
                $oEx = oxNew('oxInputException');
                $oEx->setMessage("{$sModuleId} module does not exist");
                throw $oEx;
            }
        }

        return $aModuleIds;
    }
    
    /**
     * Get all available module ids
     *
     * @return array
     */
    protected function _getAvailableModuleIds( )
    {
        if ($this->_aAvailableModuleIds === null) {
            $oConfig = oxRegistry::getConfig();

            // We are calling getModulesFromDir() because we want to refresh
            // the list of available modules. This is a workaround for OXID
            // bug.
            oxNew('oxModuleList')->getModulesFromDir($oConfig->getModulesDir());

            $this->_aAvailableModuleIds = array_keys($oConfig->getConfigParam('aModulePaths'));
        }
        return $this->_aAvailableModuleIds;
    }
    
    /**
     * Get all available module ids
     *
     * @return array
     */
    protected function _deactivateAll( )
    {
        $aModuleIds = $this->_getAvailableModuleIds();
        foreach ($aModuleIds as $sModuleId)
        {
            $this->_deactivateModule( $sModuleId );
        }
    }
    
    /**
     * Get all available module ids
     *
     * @return array
     */
    protected function _deactivateModule( $sModuleId )
    {
        if (!$this->_checkStatus( $sModuleId ))
        {
            echo 'Module '. $sModuleId . ' already deactivated';
            echo "\n";
            return;
        }
        $oModule = oxNew('oxModule');
        if (!$oModule->load($sModuleId)) {
            echo 'module '. $sModuleId .' is not loaded';
            return;
        }
        try {
            /** @var oxModuleCache $oModuleCache */
            $oModuleCache = oxNew('oxModuleCache', $oModule);
            /** @var oxModuleInstaller $oModuleInstaller */
            $oModuleInstaller = oxNew('oxModuleInstaller', $oModuleCache);

            if ($oModuleInstaller->deactivate($oModule)) {
                $this->_aViewData["updatenav"] = "1";
            }
        } catch (oxException $oEx) {
            throw $oEx;
        }
        echo 'Module '. $sModuleId . ' deactivated';
        echo "\n";
    }
    
    /**
     *
     * @param array $ymlData 
     * @return array
     */
    protected function _getList()
    {
        $oxModule = oxNew('oxModule');
        if (!$oxModule->load('event_hook')) {
            echo "Module event_hook missing!";
            return;
        } else if (!file_exists($oxModule->getModuleFullPath().'/files/libs/Spyc.php')) {
            echo "Spyc.php missing!";
            return;
        }
        include_once $oxModule->getModuleFullPath().'/files/libs/Spyc.php';

        $sModuleDeactivation = getShopBasePath().'application/commands/eventhook/module_activation.yml';
        if (!file_exists($sModuleDeactivation)) {
            return;
        }

        $ymlData = Spyc::YAMLLoad($sModuleDeactivation);
        if ($ymlData) {
            $aModuleIds=  [];
            if (isset($ymlData["1"]['whitelist'])) {
                foreach ($ymlData["1"]['whitelist'] as $sModule) {
                    array_push($aModuleIds, $sModule);
                }
                return $aModuleIds;
            }
            elseif (isset($ymlData["1"]['blacklist'])) {
                foreach ($ymlData["1"]['blacklist'] as $sModule) {
                    array_push($aModuleIds, $sModule);
                }
                return $aModuleIds;
            }
        }
        else {
            echo 'No file found';
            die();
        }
    }
}
