<?php

namespace Wutime\AddonLog;

use XF\AddOn\AddOn;
use XF\Entity\AddOn as AddOnEntity;

class Listener
{
    // addon_post_install
    public static function postInstall(
        AddOn $addOn,
        AddOnEntity $installedAddOn,
        array $json,
        array &$stateChanges
    ) {

        self::log(
            $installedAddOn->addon_id,
            $installedAddOn->title,
            'install',
            $json['version_string'] ?? ($installedAddOn->version_string ?? ''),
            '' // no prior
        );
    }

    // addon_post_upgrade
    public static function postUpgrade(
        AddOn $addOn,
        AddOnEntity $installedAddOn,
        array $json,
        array &$stateChanges
    ) {
        self::log(
            $installedAddOn->addon_id,
            $installedAddOn->title,
            'upgrade',
            $json['version_string'] ?? ($installedAddOn->version_string ?? ''),
            $stateChanges['from_version_string'] ?? ''
        );
    }

    // addon_post_uninstall
    public static function postUninstall(
        AddOn $addOn,
        string $addOnId,
        array $json
    ) {
        self::log(
            $addOnId,
            $json['title'] ?? $addOnId,
            'uninstall',
            $json['version_string'] ?? '',
            ''
        );
    }

    // addon_post_rebuild
    public static function postRebuild(
        AddOn $addOn,
        AddOnEntity $installedAddOn,
        array $json
    ) {
        self::log(
            $installedAddOn->addon_id,
            $installedAddOn->title,
            'rebuild',
            $json['version_string'] ?? ($installedAddOn->version_string ?? ''),
            $installedAddOn->version_string ?? ''
        );
    }

	protected static function log(
	    string $addonId,
	    string $title,
	    string $type,
	    string $version = '',
	    string $versionPrior = ''
	) {
	    $opts = \XF::options();

	    // helpers
	    $splitLines = static function (?string $s): array {
	        $s = (string)$s;
	        if ($s === '') return [];
	        $parts = preg_split('/\R/u', $s) ?: [];
	        $out = [];
	        foreach ($parts as $p) {
	            $p = trim(str_replace('\\', '/', (string)$p));
	            if ($p !== '') { $out[] = $p; }
	        }
	        return $out;
	    };
	    $inList = static function (string $needle, array $list): bool {
	        $n = trim(str_replace('\\', '/', $needle));
	        foreach ($list as $raw) {
	            if ($n === trim(str_replace('\\', '/', (string)$raw))) return true;
	        }
	        return false;
	    };

	    // 1) Global ignore: block both DB + posting
	    $globalOpt  = $opts->wutime_addonlog_ignoreAddOns ?? [];
	    $globalOn   = !empty($globalOpt['enabled']);
	    $globalList = $globalOn ? $splitLines($globalOpt['addons'] ?? '') : [];
	    if ($globalOn && $inList($addonId, $globalList)) {
	        return;
	    }

	    // 2) Log to DB
	    $userId = \XF::visitor()->user_id ?: 0;
	    /** @var \Wutime\AddonLog\Repository\Log $repo */
	    $repo = \XF::app()->repository('Wutime\AddonLog:Log');
	    try {
	        $repo->logAction($addonId, $title, $type, $userId, $version, $versionPrior);

	        // 3) Optional forum post
	        $threadOpt    = $opts->wualThreadAddonUpdate ?? [];
	        $threadOn     = (($threadOpt['action'] ?? 'none') === 'thread');
	        $nodeId       = (int)($threadOpt['node_id'] ?? 0);
	        $events       = (array)($threadOpt['events'] ?? []);
	        $eventAllowed = !empty($events[$type]); // install|upgrade|uninstall|rebuild
	        if (!$threadOn || $nodeId <= 0 || !$eventAllowed) {
	            return;
	        }

	        // Per-thread ignore blocks posting only
	        $threadIgnore = $splitLines($threadOpt['threadIgnoreAddons'] ?? '');
	        if ($inList($addonId, $threadIgnore)) {
	            return;
	        }

	        $app = \XF::app();

	        /** @var \XF\Entity\Forum|null $forum */
	        $forum = $app->em()->find('XF:Forum', $nodeId);
	        if (!$forum) {
	            return;
	        }

	        // Post as wualUser (user_id), fallback to current visitor if not valid
	        $poster = null;
	        $posterId = (int)($opts->wualUser ?? 0);
	        if ($posterId > 0) {
	            $poster = $app->em()->find('XF:User', $posterId);
	        }
	        if (!$poster && \XF::visitor()->user_id) {
	            $poster = \XF::visitor();
	        }

	        $create = static function () use ($forum, $addonId, $title, $type, $version, $versionPrior) {
	            /** @var \XF\Service\Thread\Creator $creator */
	            $creator = \XF::service('XF:Thread\Creator', $forum);
	            $creator->setIsAutomated();
	            $creator->setContent(
	                sprintf('[%s] %s', strtoupper($type), $title),
	                sprintf(
	                    "Add-on: %s\nType: %s\nVersion: %s\nPrevious: %s\nTime: %s",
	                    $addonId,
	                    $type,
	                    $version ?: '-',
	                    $versionPrior ?: '-',
	                    \XF::language()->dateTime(\XF::$time)
	                )
	            );
	            $creator->setPerformValidations(false);
	            $creator->save();
	        };

	        if ($poster) {
	            \XF::asVisitor($poster, $create);
	        } else {
	            $create();
	        }
	    } catch (\Exception $e) {
	        \XF::logError(sprintf(
	            "[AddonLog] Failed to log action: %s, %s, %s, %s, %s, %s. Error: %s",
	            $addonId, $title, $type, $version, $versionPrior, $userId, $e->getMessage()
	        ));
	    }
	}






}
