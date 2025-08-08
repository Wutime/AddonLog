<?php

namespace Wutime\AddonLog;

use XF\AddOn\AddOn;
use XF\Entity\AddOn as AddOnEntity;

class Listener
{
    public static function postInstall(
        AddOn $addOn,
        AddOnEntity $installedAddOn,
        array $json,
        array &$stateChanges
    ) {
        $version = $json['version_string'] ?? '';
        self::log(
            $installedAddOn->addon_id,
            $installedAddOn->title,
            'install',
            $version,
            '' // no prior version
        );
    }

    public static function postUpgrade(
        AddOn $addOn,
        AddOnEntity $installedAddOn,
        array $json,
        array &$stateChanges
    ) {
        $version = $json['version_string'] ?? '';
        $versionPrior = $installedAddOn->version_string ?? '';

        self::log(
            $installedAddOn->addon_id,
            $installedAddOn->title,
            'upgrade',
            $version,
            $versionPrior
        );
    }

    public static function postUninstall(
        AddOn $addOn,
        string $addOnId,
        array $json
    ) {
        $title = $json['title'] ?? $addOnId;
        $version = $json['version_string'] ?? '';

        self::log(
            $addOnId,
            $title,
            'uninstall',
            $version,
            '' // prior version unknown
        );
    }

    public static function postRebuild(
        AddOn $addOn,
        AddOnEntity $installedAddOn,
        array $json
    ) {
        $version = $json['version_string'] ?? '';
        $versionPrior = $installedAddOn->version_string ?? '';

        self::log(
            $installedAddOn->addon_id,
            $installedAddOn->title,
            'rebuild',
            $version,
            $versionPrior
        );
    }

    protected static function log(
        string $addonId,
        string $title,
        string $type,
        string $version = '',
        string $versionPrior = ''
    ) {
        $userId = \XF::visitor()->user_id ?: 0; // Removed CLI check

        /** @var \Wutime\AddonLog\Repository\Log $repo */
        $repo = \XF::app()->repository('Wutime\AddonLog:Log');

        try {
            $repo->logAction(
                $addonId,
                $title,
                $type,
                $userId,
                $version,
                $versionPrior
            );
        } catch (\Exception $e) {
            \XF::logError(sprintf(
                "[AddonLog] Failed to log action: %s, %s, %s, %s, %s, %s. Error: %s",
                $addonId,
                $title,
                $type,
                $version,
                $versionPrior,
                $userId,
                $e->getMessage()
            ));
        }
    }
}