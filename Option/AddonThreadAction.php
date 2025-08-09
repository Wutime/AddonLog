<?php

namespace Wutime\AddonLog\Option;

use XF\Entity\Node;
use XF\Entity\Option;
use XF\Option\AbstractOption;

class AddonThreadAction extends AbstractOption
{
    public static function renderOption(Option $option, array $htmlParams)
    {
        /** @var \XF\Repository\Node $nodeRepo */
        $nodeRepo = \XF::repository('XF:Node');
        $nodeTree = $nodeRepo->createNodeTree($nodeRepo->getFullNodeList());

        return static::getTemplate('admin:wual_option_template_addonThreadAction', $option, $htmlParams, [
            'nodeTree' => $nodeTree,
        ]);
    }

	public static function verifyOption(array &$value, Option $option)
	{
	    $value   = is_array($value) ? $value : [];
	    $action  = ($value['action'] ?? 'none') === 'thread' ? 'thread' : 'none';
	    $nodeId  = (int)($value['node_id'] ?? 0);
	    $eventsIn  = (array)($value['events'] ?? []);
	    $ignoreIn  = (string)($value['threadIgnoreAddons'] ?? '');

	    $existing = is_array($option->option_value) ? $option->option_value : [];
	    $defaults = is_array($option->default_value) ? $option->default_value : [];

	    if ($action === 'thread') {
	        // Events: trust posted values (hidden 0s are inside <xf:dependent/>)
	        $events = array_replace(
	            ['install'=>0,'upgrade'=>0,'uninstall'=>0,'rebuild'=>0],
	            $eventsIn
	        );
	        $eventsNorm = [
	            'install'   => (int)$events['install'],
	            'upgrade'   => (int)$events['upgrade'],
	            'uninstall' => (int)$events['uninstall'],
	            'rebuild'   => (int)$events['rebuild'],
	        ];

	        // Require at least one
	        if (!($eventsNorm['install'] || $eventsNorm['upgrade'] || $eventsNorm['uninstall'] || $eventsNorm['rebuild'])) {
	            $option->error(\XF::phrase('wual_please_select_at_least_one_event'), $option->option_id);
	            return false;
	        }

	        // Validate forum
	        $node = \XF::em()->find('XF:Node', $nodeId);
	        if (!$node || $node->node_type_id !== 'Forum') {
	            $option->error(\XF::phrase('wual_please_specify_valid_forum'), $option->option_id);
	            return false;
	        }

	        // Clean ignore list via existing validator (use posted text or fall back to existing/default)
	        $clean = ['enabled'=>true, 'addons'=> $ignoreIn !== '' ? $ignoreIn
	            : (string)($existing['threadIgnoreAddons'] ?? ($defaults['threadIgnoreAddons'] ?? ''))];
	        if (!\Wutime\AddonLog\Option\IgnoreAddOns::verifyOption($clean, $option)) {
	            return false;
	        }
	        $ignoreClean = $clean['addons'];

	        $value = [
	            'action'             => 'thread',
	            'node_id'            => $nodeId,
	            'events'             => $eventsNorm,
	            'threadIgnoreAddons' => $ignoreClean
	        ];
	        return true;
	    }

	    // action === 'none' → DO NOT clobber previous selections
	    $value = [
	        'action'             => 'none',
	        // remember last chosen forum (don’t validate now)
	        'node_id'            => $nodeId ?: (int)($existing['node_id'] ?? 0),
	        // keep prior events or defaults
	        'events'             => (array)($existing['events'] ?? $defaults['events'] ?? ['install'=>1,'upgrade'=>1,'uninstall'=>1,'rebuild'=>0]),
	        // keep prior ignore list or defaults (no validation while hidden)
	        'threadIgnoreAddons' => (string)($existing['threadIgnoreAddons'] ?? ($defaults['threadIgnoreAddons'] ?? ''))
	    ];
	    return true;
	}



}