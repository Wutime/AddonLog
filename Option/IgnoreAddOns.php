<?php

namespace Wutime\AddonLog\Option;

use XF\Entity\Option;
use XF\Option\AbstractOption;

class IgnoreAddOns extends AbstractOption
{
    public static function verifyOption(array &$value, Option $option)
    {
        // If the option is disabled, skip validation and set empty addons
        if (empty($value['enabled'])) {
            $value['addons'] = '';
            return true;
        }

        // Get the textarea input (addons)
        $input = $value['addons'] ?? '';

        // Accept textarea string or array; normalize to array of lines
        if (is_string($input)) {
            $lines = preg_split('/\R/u', trim($input)) ?: [];
        } elseif (is_array($input)) {
            $lines = $input;
        } else {
            $lines = [];
        }

        $valid = [];
        $seen = [];
        // Regex: Vendor/AddOn (e.g., Wutime/AddonLog)
        $pattern = '/^[A-Za-z][A-Za-z0-9_-]*\/[A-Za-z][A-Za-z0-9_-]*$/';

        foreach ($lines as $index => $line) {
            $id = trim((string)$line);
            if ($id === '') {
                continue;
            }

            // Normalize slashes and strip internal whitespace
            $id = str_replace('\\', '/', $id);
            $id = preg_replace('/\s+/u', '', $id);

            if (!preg_match($pattern, $id)) {
                $option->error(\XF::phrase(
                    'wual_invalid_addon_format',
                    ['line' => $index + 1, 'value' => $id]
                ), $option->option_id);
                return false;
            }

            if (!isset($seen[$id])) {
                $seen[$id] = true;
                $valid[] = $id;
            }
        }

        // Convert the valid array back to a string (one per line)
        $value['addons'] = implode("\n", $valid);
        return true;
    }
}