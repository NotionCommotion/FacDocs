<?php

/*
 * This file is part of the FacDocs project.
 *
 * (c) Michael Reed villascape@gmail.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Security\Service;

// ######  Only used to initially create roles.  Delete after complete.  ####
final class AccessRoleCreatorService
{
    private const CRUD = ['create' => 'MANAGE', 'read' => 'READ', 'update' => 'UPDATE', 'delete' => 'MANAGE', 'manage_acl' => 'MANAGE'];
    private const CLASS_MAP = ['Project', 'CustSpec' => 'CustomSpecification', 'Asset', 'Document', 'Vendor', 'DocGroup' => 'DocumentGroup', 'TenantUser', 'VendorUser', 'Template', 'Archive', 'Tenant'];

    public static function createDefaultRoleYaml(string $tag):string
    {
        return self::toYaml(['parameters' =>[$tag=>self::getRoleDefinitions()]]);
    }
    // Use yaml_emit() instead if installed.
    private static function toYaml(array $input, string $padding=''):string
    {
        $output = [];
        foreach($input as $key=>$value) {
            $output[] = is_array($value)
            ?sprintf('%s%s:%s%s', $padding, $key, PHP_EOL, self::toYaml($value, $padding.chr(9)))
            :sprintf('%s%s: %s', $padding, $key, $value);
        }
        return implode(PHP_EOL, $output);
    }
    private static function getRoleDefinitions()
    {
        $classes = self::getEntities();
        $rs = [];
        foreach(self::CLASS_MAP as $key=>$class) {
            $arr = [];
            $tag = is_int($key)?$class:$key;
            $name = strtoupper(self::toSnake($tag));
            foreach(self::CRUD as $action => $role) {
                $arr[$action] = sprintf('ROLE_%s_%s', $role, $name);
            }
            $arr['manage_acl'] = 'ROLE_MANAGE_ACL_'.$name;
            $rs[$classes[$class]] = $arr;
        }
        return $rs;
    }
    private static function toSnake(string $input):string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
    private static function getEntities():array
    {
        $classes = array_filter(get_declared_classes(), function($class) {
            return ($parts = explode('\\', $class)) && ('App' === $parts[0]??null) && ('Entity' === $parts[1]??null);
        });
        return array_combine(array_map(function($class){return ($parts = explode('\\', $class))?end($parts):NULL;}, $classes),$classes);
    }
}