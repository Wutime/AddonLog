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

    public function logAction($addonId, $title, $type, $userId)
    {
        $log = $this->em->create('Wutime\AddonLog:Log');
        $log->bulkSet([
            'addon_id' => $addonId,
            'title' => $title,
            'type' => $type,
            'log_date' => \XF::$time,
            'user_id' => $userId
        ]);
        $log->save();
    }
}
