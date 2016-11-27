<?php

/**
 * Generate migration file
 */
class GenerateEventhookCommand extends oxConsoleCommand
{

    /**
     * Configure current command
     */
    public function configure()
    {
        $this->setName('g:eventhook');
        $this->setDescription('Generate new eventhook migration file');
    }

    /**
     * Output help text of command
     *
     * @param oxIOutput $oOutput
     */
    public function help(oxIOutput $oOutput)
    {
        $oOutput->writeLn('Usage: g:eventhook <module_id> <version>');
        $oOutput->writeLn();
        $oOutput->writeLn('Generates blank migration class in a module.');
        $oOutput->writeLn('Migration name depends on words you have written.');
        $oOutput->writeLn();
        $oOutput->writeLn('Example: g:eventhook my_module_id 1.1');
    }

    /**
     * Get template path
     *
     * This allows us to override where template file is stored
     *
     * @return string
     */
    protected function _getTemplatePath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'eventhook.tpl';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(oxIOutput $oOutput)
    {
        $sTemplatePath = $this->_getTemplatePath();
        $aArguments = $this->_parseEventhookArgumentsFromInput();

        $sModulePath = '';
        if (!$aArguments['module_id']) {
            $oOutput->writeLn("Module id was not provided!");
            return;
        } else {
            $oxModule = oxNew('oxModule');
            if ($oxModule->load($aArguments['module_id'])) {
                $sModulePath = rtrim($oxModule->getModuleFullPath(), '/').'/';
                $sModuleMigrationPath = $sModulePath.'migration/';
            } else {
                $oOutput->writeLn("Module with Id \"{$aArguments['module_id']}\" could not be found!");
                $oxModuleList = oxNew('oxModuleList');
                $aModuleIds = $oxModuleList->getModuleIds();
                echo "Available module Ids are:".PHP_EOL;
                foreach ($aModuleIds as $deltaModuleIds => $sModuleId) {
                    echo "$sModuleId".PHP_EOL;
				}
                return;
            }
        }

        if (!$aArguments['version']) {
            $oOutput->writeLn("Version missing!");
            $oOutput->writeLn("Example: {$aArguments['module_id']} 1.0");
            return;
        }

        if (!file_exists($sModuleMigrationPath)) {
            if (mkdir($sModuleMigrationPath, 0777, true)) {
                echo "Migration path successfully created.".PHP_EOL;
            } else {
                echo "Migration directory could not be created in module path!".PHP_EOL;
                return;
            }
        }

        $sVersion = str_replace('.', '_', $aArguments['version']);
        $sMigrationFileName = 'event_hook'.$sVersion.'.php';
        $sMigrationClassName = 'event_hook'.$sVersion;
        $sMigrationFilePath = $sModuleMigrationPath.$sMigrationFileName;

        /** @var Smarty $oSmarty */
        $oSmarty = oxRegistry::get('oxUtilsView')->getSmarty();
        $oSmarty->assign('sMigrationName', $sMigrationClassName);
        $sContent = $oSmarty->fetch($sTemplatePath);

        if (file_exists($sMigrationFilePath)) {
            $sAnswer = $this->_askForMigrationOverwrite($sMigrationFilePath);
            if ($sAnswer == 'y') {
                file_put_contents($sMigrationFilePath, $sContent);
            } else {
                return;
            }
        } else {
            file_put_contents($sMigrationFilePath, $sContent);
        }

        $oOutput->writeLn("Sucessfully generated $sMigrationFilePath.");
    }

    /**
     * Parse migration name from input arguments
     *
     * @return array
     */
    protected function _parseEventhookArgumentsFromInput()
    {
        $aRet = Array(
            'module_id' => '',
            'version' => ''
        );

        $oInput = $this->getInput();

        $aTokens = $oInput->getArguments();
        $aArguments = array_slice($aTokens, 1, 2);

        if (is_string($aArguments[0]) && trim($aArguments[0]) != '') {
            $sModuleId = trim($aArguments[0]);
            $aRet['module_id'] = $sModuleId;
        }

        if (is_string($aArguments[1]) && trim($aArguments[1]) != '') {
            $sVersion = trim($aArguments[1]);
            if (preg_match('/^[1-9][\d]*(.[1-9][\d]*)*(.\*)?|\*$/', $sVersion)) {
                $aRet['version'] = $sVersion;
            }
        }

        return $aRet;
    }

    /**
     * Ask for migration tokens input
     *
     * @return array
     */
    protected function _askForMigrationOverwrite($sMigrationFilePath)
    {
        $oInput = $this->getInput();
        $sRet = trim($oInput->prompt("Overwrite existing file $sMigrationFilePath? [n]"));
        if ($sRet != '') {
            $sRet = strtolower($sRet);
        } else {
            $sRet = 'n';
        }
        return $sRet;
    }
}