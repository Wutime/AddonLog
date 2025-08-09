<?php

namespace Wutime\AddonLog\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Updates extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {


	    if (!\XF::visitor()->hasPermission('general', 'viewAddonUpdates')) {
	        return $this->noPermission();
	    }
    	
    	$opt = \XF::options();
        $page = max(1, $this->filterPage());
        $perPage = 20;

        $type = $this->filter('type', 'str');
        $allowed = ['install', 'upgrade', 'uninstall', 'rebuild'];
        if ($type && !in_array($type, $allowed, true)) {
            $type = '';
        }

        $finder = $this->finder('Wutime\AddonLog:Log')
            ->with('User')
            ->order('log_date', 'DESC');

        if ($type) {
            $finder->where('type', $type);
        }

        $total = $finder->total();
        $logs = $finder->limitByPage($page, $perPage)->fetch();

        $viewParams = [
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'type' => $type,
            'linkParams' => $type ? ['type' => $type] : []
        ];


		$am = $this->app()->addOnManager();

		$addonFa = [];          // addon_id => ['type' => 'regular|solid|brands', 'icon' => 'fa-forward']
		$addonImgHtml = [];     // addon_id => '<img src="data:...">'
		$seen = [];

		foreach ($logs as &$log) {


			if ($opt->wualUser) {
				$log['user_id'] = $opt->wualUser;
			}

		    $id = $log->addon_id;
		    if (!$id || isset($seen[$id])) { continue; }
		    $seen[$id] = true;

		    $addOn = $am->getById($id);

		    if (!$addOn) {
		        // completely removed: show a default FA question mark
		        $addonFa[$id] = ['type' => 'regular', 'icon' => 'fa-question-circle'];
		        continue;
		    }

		    // 1) Font Awesome icon in addon.json?
		    if ($addOn->hasFaIcon()) {
		        $icon = trim($addOn->icon);

		        // normalize to <xf:fa> args
		        if (preg_match('~\b(fas|far|fab)\b~', $icon, $m)) {
		            $typeMap = ['fas' => 'solid', 'far' => 'regular', 'fab' => 'brands'];
		            $type = $typeMap[$m[1]] ?? 'regular';
		            $name = trim(preg_replace('~\b(fas|far|fab)\b~', '', $icon));
		        } elseif (preg_match('~\bfa-(solid|regular|brands)\b~', $icon, $m)) {
		            $type = $m[1];
		            $name = trim(preg_replace('~\bfa-(solid|regular|brands)\b~', '', $icon));
		        } else {
		            $type = 'regular';
		            $name = $icon; // e.g. "fa-forward"
		        }

		        $addonFa[$id] = ['type' => $type, 'icon' => $name];
		        continue;
		    }

		    // 2) Image file present? use data URI provided by XF
		    if ($addOn->hasIcon()) {
		        $uri = $addOn->getIconUri(); // data:image/...;base64,....
		        if ($uri) {
		            $addonImgHtml[$id] = '<img src="' . $uri . '" alt="" loading="lazy" width="24" height="24" style="object-fit:contain">';
		        }
		        continue;
		    }

		    // 3) No icon defined → nothing (or set a different fallback if you want)
		}

		$viewParams['logs'] = $logs;
		$viewParams['addonFa'] = $addonFa;
		$viewParams['addonImgHtml'] = $addonImgHtml;

        return $this->view('Wutime\AddonLog:Updates', 'wual_addon_updates', $viewParams);
    }

	private function buildFaHtml(string $icon): string
	{
	    // normalize family (support FA6 names too)
	    $hasFamily = preg_match('~\b(fas|far|fab)\b~', $icon);
	    if (!$hasFamily) {
	        // map fa-solid/fa-regular/fa-brands to fas/far/fab
	        $icon = preg_replace('~\bfa-solid\b~', 'fas', $icon);
	        $icon = preg_replace('~\bfa-regular\b~', 'far', $icon);
	        $icon = preg_replace('~\bfa-brands\b~', 'fab', $icon);
	        $hasFamily = preg_match('~\b(fas|far|fab)\b~', $icon);
	        if (!$hasFamily) {
	            $icon = 'far ' . $icon; // default to regular
	        }
	    }

	    // Don’t force fa-3x here; your column is 24px, keep it compact.
	    return '<i class="fa--xf ' . htmlspecialchars(trim($icon), ENT_QUOTES) . '"></i>';
	}

}