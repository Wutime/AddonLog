<?php

namespace Wutime\AddonLog\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;
use XF\Db\Schema\Create;
use XF\Db\Schema\Alter;

class Log extends Entity
{
    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_wu_addon_log';
        $structure->shortName = 'Wutime\AddonLog:Log';
        $structure->primaryKey = 'addon_log_id';
        $structure->columns = [
            'addon_log_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'addon_id' => ['type' => self::BINARY, 'maxLength' => 50, 'required' => true],
            'title' => ['type' => self::STR, 'maxLength' => 75, 'required' => true],
            'type' => [
                'type' => self::STR,
                'allowedValues' => ['install', 'upgrade', 'uninstall', 'enable', 'disable', 'rebuild'], 
                'required' => true
            ],
            'log_date' => ['type' => self::UINT, 'required' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'version_string' => ['type' => self::STR, 'maxLength' => 20, 'default' => ''],
            'version_string_prior' => ['type' => self::STR, 'maxLength' => 20, 'default' => '']
        ];

        $structure->getters = [];
        $structure->relations = [
            'User' => [
                'entity' => 'XF:User',
                'type' => self::TO_ONE,
                'conditions' => 'user_id',
                'primary' => true
            ]
        ];
        $structure->defaultWith = ['User'];

        return $structure;
    }
}