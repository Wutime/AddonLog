<?php

namespace Wutime\AddonLog\Repository;

use XF\Mvc\Entity\Finder;
use XF\Mvc\Entity\Repository;

class Log extends Repository
{
    public function findLogsForList(): Finder
    {
        return $this->finder('Wutime\AddonLog:Log')->order('log_date', 'DESC');
    }

    public function logAction($addonId, $title, $type, $userId, $version = '', $versionPrior = '')
    {
        $log = $this->em->create('Wutime\AddonLog:Log');
        $log->bulkSet([
            'addon_id' => $addonId,
            'title' => $title,
            'type' => $type,
            'log_date' => \XF::$time,
            'user_id' => $userId,
            'version_string' => substr($version, 0, 20), // Ensure fits VARCHAR(20)
            'version_string_prior' => substr($versionPrior, 0, 20) // Ensure fits VARCHAR(20)
        ]);
        $log->save();
    }
}