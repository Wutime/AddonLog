<?php

namespace Wutime\AddonLog;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;
use XF\Db\Schema\Alter;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;





/*

IMPORTANT: ** REVIEW **

 */

    static $usesComposer    = false;
    static $install         = false;
    static $upgrade         = false;
    static $uninstall       = false;









/*

CHECK REQUIREMENTS

 */



    public function checkRequirements(&$errors = [], &$warnings = [])
    {

        if (self::$usesComposer) {
            $vendorDirectory = sprintf("%s/vendor", $this->addOn->getAddOnDirectory());
            if (!file_exists($vendorDirectory))
            {
                $errors[] = "vendor folder does not exist - cannot proceed with addon install";
            }
        }

    }





/*

INSTALL

 */


    public function installStep1()
    {


    	$this->setInstallUpgradeVersion();

        $this->schemaManager()->createTable('xf_wu_addon_log', function (Create $table) {
            $table->addColumn('addon_log_id', 'int')->autoIncrement();
            $table->addColumn('addon_id', 'varbinary', 50);
            $table->addColumn('title', 'varchar', 75);
            $table->addColumn('type', 'enum')->values(['install', 'upgrade', 'uninstall', 'delete', 'enable', 'disable', 'rebuild']);
            $table->addColumn('log_date', 'int');
            $table->addColumn('user_id', 'int');
            $table->addColumn('version_string', 'varchar', 20)->setDefault('');
            $table->addColumn('version_string_prior', 'varchar', 20)->setDefault('');
            $table->addPrimaryKey('addon_log_id');
            $table->addKey('log_date');
        });
    }






/*

UPGRADE

 */


	public function upgrade1000270Step1()
	{
	    $sm = $this->schemaManager();
	    $db = $this->db();

	    // 1) Make sure both old and new values exist (additive change)
	    $sm->alterTable('xf_wu_addon_log', function (Alter $table) {
	        $table->changeColumn('type', 'enum')->values([
	            'install', 'upgrade', 'uninstall', 'enable', 'disable', 'rebuild', 'delete'
	        ]);
	    });

	    // 2) Remap data while both values are valid
	    $db->query("
	        UPDATE xf_wu_addon_log
	        SET type = 'uninstall'
	        WHERE type = 'delete'
	    ");

	    // 3) Remove the old value
	    $sm->alterTable('xf_wu_addon_log', function (Alter $table) {
	        $table->changeColumn('type', 'enum')->values([
	            'install', 'upgrade', 'uninstall', 'enable', 'disable', 'rebuild'
	        ]);
	    });
	}






/*

POST UPGRADE

 */

    public function postUpgrade($previousVersion, array &$stateChanges)
    {
        $this->setInstallUpgradeVersion();
    }











/*

UN-INSTALL

 */



    public function uninstall(array $stepParams = [])
    {
        $this->schemaManager()->dropTable('xf_wu_addon_log');
    }










/*

MISC.

 */



	protected function applyPermissionToGroups(array $userGroupIds, string $permissionGroupId, string $permissionId, string $value = 'allow', int $valueInt = 0)
	{
	    $db = \XF::db();

	    foreach ($userGroupIds as $userGroupId) {
	        $db->query("
	            INSERT INTO xf_permission_entry
	                (user_id, user_group_id, permission_group_id, permission_id, permission_value, permission_value_int)
	            VALUES
	                (0, ?, ?, ?, ?, ?)
	            ON DUPLICATE KEY UPDATE
	                permission_value = VALUES(permission_value),
	                permission_value_int = VALUES(permission_value_int)
	        ", [$userGroupId, $permissionGroupId, $permissionId, $value, $valueInt]);
	    }
	}




    protected function setInstallUpgradeVersion()
    {
        $version = $this->getAddonVersion();
        $optionId = $this->generateOptionId();
        $db = \XF::db();

        // Check if the option already exists
        $existingOption = $db->fetchOne("SELECT option_value FROM xf_option WHERE option_id = ?", $optionId);

        if ($existingOption === false) {
            if (!self::$install) {
                $this->stepX('install');
                self::$install = true;
            }
        } else {
            // Upgrade
            if (!self::$upgrade) {
                $this->stepX('upgrade');
                self::$upgrade = true;
            }
            $db->update('xf_option', ['option_value' => $version], 'option_id = ?', $optionId);
        }
    }

    private static function stepX($step)
    {

    }

    protected function getInstalledVersion()
    {
        $optionId = $this->generateOptionId();
        $db = \XF::db();
        return $db->fetchOne("SELECT option_value FROM xf_option WHERE option_id = ?", $optionId);
    }

    protected function getAddonVersion()
    {
        $addonJsonPath = $this->getAddonDirectory() . DIRECTORY_SEPARATOR . 'addon.json';
        $addonJson = file_get_contents($addonJsonPath);
        $addonData = json_decode($addonJson, true);
        return $addonData['version_id'];
    }

    protected function getAddonDirectory()
    {
        // Get add-on (remove leading \ and replace other slashes with /)
        $addon = str_replace('\\', '/', ltrim($this->addOn->addon_id, '\\'));

        // Build the full add-on directory path dynamically
        return \XF::getAddOnDirectory() . '/' . $addon;
    }

    protected function generateOptionId()
    {
        // Convert the addon name to lower case and replace slashes with underscores, then append "_install_version"
        $optionId = strtolower(str_replace(['/', '\\'], '_', $this->addOn->addon_id)) . '_install_version';
        return $optionId;
    }







}