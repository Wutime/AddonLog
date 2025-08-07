<?php

namespace Wutime\AddonLog;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Create;


class Setup extends AbstractSetup
{
	use StepRunnerInstallTrait;
	use StepRunnerUpgradeTrait;
	use StepRunnerUninstallTrait;


    public function installStep1()
    {
		$this->schemaManager()->createTable('xf_wu_addon_log', function (Create $table) {
		    $table->addColumn('addon_log_id', 'int')->autoIncrement();
		    $table->addColumn('addon_id', 'varbinary', 50);
		    $table->addColumn('title', 'varchar', 75);
		    $table->addColumn('type', 'enum')->values(['install', 'upgrade', 'delete', 'enable', 'disable', 'rebuild']);
		    $table->addColumn('log_date', 'int');
		    $table->addColumn('user_id', 'int');
		    $table->addColumn('version_string', 'varchar', 20)->setDefault('');
		    $table->addColumn('version_string_prior', 'varchar', 20)->setDefault('');
		    $table->addPrimaryKey('addon_log_id');
		    $table->addKey('log_date');
		});

    }

    public function uninstall(array $stepParams = [])
    {
        $this->schemaManager()->dropTable('xf_wu_addon_log');
    }
}