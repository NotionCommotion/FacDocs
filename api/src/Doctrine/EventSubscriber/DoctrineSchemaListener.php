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
/*
Doesn't currently do anything.  Used to keep Doctrine from updating manual settings.
https://www.liip.ch/en/blog/doctrine-and-generated-columns
https://github.com/doctrine/orm/issues/6434
https://stackoverflow.com/questions/53448034/in-doctrine-how-to-ignore-specific-column-from-update-schema-command/53548294
*/

namespace App\Doctrine\EventSubscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\SchemaAlterTableAddColumnEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableRemoveColumnEventArgs;

use Doctrine\DBAL\Event\SchemaAlterTableEventArgs;
use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;
use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;

use Doctrine\DBAL\Events;

/**
 * Purpose is to prevent Doctrine from taking some actions when migrating.  Not complete and not being used..
 */
class DoctrineSchemaListener implements EventSubscriber
{
    public function onSchemaAlterTable(SchemaAlterTableEventArgs $args)
    {
        if($args->getTableDiff()->name === 'project' && $args->getTableColumn()['field'] ==='id') {
            $args->preventDefault();
        }
    }
    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $args)
    {
        if($args->getTable() === 'project' && $args->getTableColumn()['field'] ==='id') {
            $args->preventDefault();
        }
    }
    public function onSchemaColumnDefinition(SchemaColumnDefinitionEventArgs $args)
    {
        if($args->getTable() === 'project' && $args->getTableColumn()['field'] ==='id') {
            printf('method: %s isDefaultPrevented: %s'.PHP_EOL, __METHOD__, $args->isDefaultPrevented()?'T':'F');
            //$args->preventDefault();
        }
    }
    public function onSchemaIndexDefinition(SchemaIndexDefinitionEventArgs $args)
    {
        if($args->getTable() === 'project' && $args->getTableColumn()['field'] ==='id') {
            printf('method: %s isDefaultPrevented: %s'.PHP_EOL, __METHOD__, $args->isDefaultPrevented()?'T':'F');
            //$args->preventDefault();
        }
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return string[]
     */
    public function getSubscribedEvents()
    {
        return [
            Events::onSchemaColumnDefinition,
            Events::onSchemaIndexDefinition,
        ];
    }
}
