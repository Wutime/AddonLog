<?php

namespace Wutime\AddonLog\Pub\Controller;

use XF\Mvc\ParameterBag;
use XF\Pub\Controller\AbstractController;

class Updates extends AbstractController
{
    public function actionIndex(ParameterBag $params)
    {
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
            'logs' => $logs,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'type' => $type,
            'linkParams' => $type ? ['type' => $type] : []
        ];


		// --- replace your current icon block with this ---
		$am = $this->app()->addOnManager();

		$addonFa = [];          // addon_id => ['type' => 'regular|solid|brands', 'icon' => 'fa-forward']
		$addonImgHtml = [];     // addon_id => '<img src="data:..." ...>'
		$seen = [];

		$root = \XF::getRootDirectory();

		foreach ($logs as $log) {
		    $id = $log->addon_id;
		    if (!$id || isset($seen[$id])) { continue; }
		    $seen[$id] = true;

		    $addOn = $am->getById($id);

		    if (!$addOn) {
		        // Add-on is completely gone → show FA question icon
		        $addonFa[$id] = ['type' => 'regular', 'icon' => 'fa-question-circle'];
		        continue;
		    }

		    $json = $addOn->getJson();
		    $icon = $json['icon'] ?? '';
		    if (!$icon) { continue; }                  // normalize

		    // Font Awesome?
		    if (preg_match('~\b(fas|far|fab)\b|\bfa-(solid|regular|brands)\b|^fa-~', $icon)) {
		        if (preg_match('~\b(fas|far|fab)\b~', $icon, $m)) {
		            $typeMap = ['fas' => 'solid', 'far' => 'regular', 'fab' => 'brands'];
		            $type = $typeMap[$m[1]] ?? 'regular';
		            $name = trim(preg_replace('~\b(fas|far|fab)\b~', '', $icon));
		        } elseif (preg_match('~\bfa-(solid|regular|brands)\b~', $icon, $m)) {
		            $type = $m[1];
		            $name = trim(preg_replace('~\bfa-(solid|regular|brands)\b~', '', $icon));
		        } else {
		            $type = 'regular';
		            $name = $icon;
		        }
		        $addonFa[$id] = ['type' => $type, 'icon' => $name];
		        continue;
		    }

		    // Image file → embed as data URI
		    $base = $root . '/src/addons/' . $id;
		    $candidates = [
		        $base . '/_data/' . $icon,          // standard
		        $base . '/' . $icon,                // some put it at root
		        $base . '/_data/icons/' . $icon,    // occasional subdir
		    ];

		    $path = null;
		    foreach ($candidates as $p) {
		        if (is_file($p) && is_readable($p)) { $path = $p; break; }
		    }
		    if (!$path) { continue; }

		    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
		    $mime = match ($ext) {
		        'png'  => 'image/png',
		        'jpg', 'jpeg' => 'image/jpeg',
		        'gif'  => 'image/gif',
		        'webp' => 'image/webp',
		        'svg'  => 'image/svg+xml',
		        default => 'application/octet-stream'
		    };

		    $data = @file_get_contents($path);
		    if ($data === false) { continue; }

		    $dataUri = 'data:' . $mime . ';base64,' . base64_encode($data);
		    $addonImgHtml[$id] = '<img src="' . $dataUri . '" alt="" loading="lazy" width="60" height="60" style="object-fit:contain">';
		}

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