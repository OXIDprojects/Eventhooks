<?php

/*
 * This file is part of the OXID Console package.
 *
 * (c) Alexander Kolinko <github@ako.li>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Activate Modules command
 *
 * Clears out OXID cache from tmp folder
 */
class ActivateModulesCommand extends oxConsoleCommand
{

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('modules:activate');
        $this->setDescription('- Activating all modules');
        $this->setDescription('- Activating modules from whitelist  -> see foo.yml');
        $this->setDescription('- Activating modules exkl. blacklist -> see foo.yml');
        $this->setDescription('- Activating module with "Id" -> Coming soon');
    }

    /**
     * Help-Printer
     * @param oxIOutput $oOutput output-ressource
     */
    public function help(oxIOutput $oOutput)
    {
        $oOutput->writeLn('Usage: modules:activate [options] [<module_id> <other_module_id>...]');
        $oOutput->writeLn();
        $oOutput->writeLn('This command activate OXID modules');
        $oOutput->writeln();
        $oOutput->writeLn('Available options:');
        $oOutput->writeLn('  -a, --all   activate all modules');
        $oOutput->writeLn('  -i  --id  Only activates the named module(s)');
        $oOutput->writeLn('      --yml   activate modules by yaml-File');
    }

    /**
     * Main execute-methode
     * @param oxIOutput $oOutput output-ressource
     */
    public function execute(oxIOutput $oOutput)
    {
        $this->_atStart();
        $oInput = $this->getInput();
        if ($oInput->hasOption(array('i', 'id'))) {
            $aExc = $this->activateById($oInput->getArguments());
        } elseif ($oInput->hasOption(array('yml', 'yml'))) {
            $aExc = $this->activateByYml();
        } elseif ($oInput->hasOption(array('a', 'all'))) {
            $aExc = $this->activateAll();
        } else {
            $this->help($oOutput);
        }
        
        $this->_writeExc($oOutput, $aExc);
        $this->_atEnd();
        $oOutput->writeLn('finish');
    }

    /**
     * Write Exception
     * @param oxIOutput $output output-ressource
     * @param array     $aExc   exceptionsarray
     */
    protected function _writeExc($output, $aExc)
    {
        if ($aExc && !empty($aExc)) {
            $output->writeLn('Got exceptions while activating modules:');
            foreach ($aExc as $item) {
                if ($item) {
                    $output->writeLn(var_export($item, true));
                }
            }
        }
    }

    /**
     * Functions for the start
     */
    protected function _atStart()
    {
        if (file_exists(getShopBasePath() . "event_hook.yml")) {
            die("Lockfile exists\n");
        }
        $eventfile = fopen(getShopBasePath() . "event_hook.yml", "w");
        fclose($eventfile);
    }

    /**
     * Functions for the end
     */
    protected function _atEnd()
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
        $eventsData = Spyc::YAMLLoad('event_hook.yml');
        if (isset($eventsData['events']['generateViews'])) {
            $oShop = oxNew('oxshop');
            $oShop->generateViews();
        }
        unlink(getShopBasePath() . "event_hook.yml");
    }

    /**
     * Activating module(s) by Yml-File
     * @return array
     */
    public function activateByYml()
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

        $sModuleActivation = getShopBasePath().'application/commands/eventhook/module_activation.yml';
        if (!file_exists($sModuleActivation)) {
            return;
        }
        $ymlData = Spyc::YAMLLoad($sModuleActivation);

        if (isset($ymlData[1]) && !empty($ymlData[1])) {
            $aExc = $this->getModuleListByYml($ymlData);
        }
        return $aExc;
    }

    /**
     * Activating module(s) by moduleId
     * @param array $args Input-arguments
     * @return array
     */
    public function activateByID($args)
    {
        if (count($args) < 2) { // Note: first argument is command name
            die("Please specify at least one module\n");
        }
        $aModuleIds = $args;
        array_shift($aModuleIds);
        foreach ($aModuleIds as $sModuleId) {
            $moduleExc = $this->activateOne($sModuleId);
            if ($moduleExc) {
                $moduleExcs[] = $moduleExc;
            }
        }
        return $moduleExcs;
    }

    /**
     * Activate one module by id
     * @param string $sId ModuleID
     * @return array
     */
    public function activateOne($sId)
    {
        try {
            $moduleExc = array();
            $oModule = oxNew('oxModule');
            if (!$oModule->load($sId)) {
                $moduleExc[$sId] = $sId . ": cannot be load";
            } else {
                /** @var oxModuleCache $oModuleCache */
                $oModuleCache = oxNew('oxModuleCache', $oModule);
                /** @var oxModuleInstaller $oModuleInstaller */
                $oModuleInstaller = oxNew('oxModuleInstaller', $oModuleCache);
                if (!$oModuleInstaller->activate($oModule)) {
                    $moduleExc[$sId] = $sId . ": cannot activate";
                }
            }
        } catch (oxException $oEx) {
            $moduleExc[$sId] = $oEx->getMessage();
        }
        return $moduleExc;
    }

    /**
     * Activate all existing modules
     * @return array Exceptions-/Warningslist
     */
    public function activateAll()
    {
        $aList = $this->getModuleList();
        if ($aList) {
            foreach ($aList as $module) {
                if ($module) {
                    $sModule = $module->getId();
                    $moduleExc = $this->activateOne($sModule);
                    if ($moduleExc) {
                        $moduleExcs[] = $moduleExc;
                    }
                }
            }
        }
        
        return $moduleExcs;
    }

    /**
     * Modulelist getter
     * @return array Modulelist
     */
    public function getModuleList()
    {
        /** @var oxModule $oModule */
        $oModuleList = oxNew('oxModuleList');
        $sModulesDir =  oxRegistry::getConfig()->getModulesDir();
        $aModules = $oModuleList->getModulesFromDir($sModulesDir);
        return $aModules;
    }

    /**
     * Modulelist getter by Yml-File
     * @param array $ymlData yml-Array
     * @return array Modulelist
     */
    public function getModuleListByYml($ymlData)
    {
        if (isset($ymlData["1"]['whitelist'])) {
            foreach ($ymlData["1"]['whitelist'] as $sModule) {
                $moduleExc[] = $this->activateOne($sModule);
            }
        } elseif (isset($ymlData["1"]['blacklist'])) {
            $blackList = $ymlData["1"]['blacklist'];
            $aList = $this->getModuleList();
            foreach ($aList as $oModule) {
                if (!array_key_exists($oModule->getId(), $blackList)) {
                    $moduleExc[] = $this->activateOne($oModule->getId());
                }
            }
        }
        return $moduleExc;
    }
}
