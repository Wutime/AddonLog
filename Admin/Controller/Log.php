<?php

namespace Wutime\AddonLog\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class Log extends AbstractController
{
    public function actionIndex()
    {
        $page = $this->filterPage(); // current page number from query string
        $perPage = 20; // adjust if you want more/less per page

        /** @var \Wutime\AddonLog\Repository\Log $logRepo */
        $logRepo = $this->repository('Wutime\AddonLog:Log');

        $finder = $logRepo->findLogsForList();

        $total = $finder->total();

        $logs = $finder
            ->limitByPage($page, $perPage)
            ->fetch();

        return $this->view(
            'Wutime\AddonLog:Log\List',
            'wutime_addonlog_log_list',
            [
                'logs' => $logs,
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total
            ]
        );
    }
}
