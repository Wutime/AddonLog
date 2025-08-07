<?php

namespace Wutime\AddonLog\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Log extends AbstractController
{
    public function actionIndex()
    {
        $logRepo = \XF::app()->repository('Wutime\AddonLog:Log');

        $logs = $logRepo->findLogsForList()->fetch();

        return $this->view('Wutime\AddonLog:Log\List', 'wutime_addonlog_log_list', [
            'logs' => $logs
        ]);
    }
}
