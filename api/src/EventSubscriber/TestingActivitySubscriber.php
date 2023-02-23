<?php
declare(strict_types=1);
namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LoadClassMetadataEventArgs;
//use Doctrine\Persistence\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\OnClearEventArgs;
use Psr\Log\LoggerInterface;

use ReflectionProperty;
use ReflectionClass;

class TestingActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger){}

    // this method can only return the event names; you cannot define a
    // custom method name to execute when each event triggers
    public function getSubscribedEvents(): array
    {
        return [
            Events::preRemove,
            Events::postRemove,
            Events::prePersist,
            Events::postPersist,
            Events::preUpdate,
            Events::postUpdate,
            Events::postLoad,
            Events::loadClassMetadata,
            Events::onClassMetadataNotFound,
            Events::preFlush,
            Events::onFlush,
            Events::postFlush,
            Events::onClear,
        ];
    }

    // callback methods must be called exactly like the events they listen to;
    // they receive an argument of type LifecycleEventArgs, which gives you access
    // to both the entity object of the event and the entity manager itself
    public function preRemove(LifecycleEventArgs $args):void
    {
        $this->logActivity($args);
    }
    public function postRemove(LifecycleEventArgs $args):void
    {
        $this->logActivity($args);
    }
    public function prePersist(LifecycleEventArgs $args):void
    {
        $this->logPrePersist($args);
    }
    public function postPersist(LifecycleEventArgs $args):void
    {
        $this->logActivity($args);
    }
    public function preUpdate(PreUpdateEventArgs $args):void
    {
        $this->logActivity($args);
    }
    public function postUpdate(LifecycleEventArgs $args):void
    {
        $this->logActivity($args);
    }
    public function postLoad(LifecycleEventArgs $args):void
    {
        $this->logActivity($args);
    }
    public function loadClassMetadata(LoadClassMetadataEventArgs $args):void
    {
        $this->logOtherActivity($args);
    }
    public function onClassMetadataNotFound(OnClassMetadataNotFoundEventArgs $args):void
    {
        $this->logActivityWithClassName($args, $args->getClassName());
    }
    public function preFlush(PreFlushEventArgs $args):void
    {
        $this->logOtherImportantActivity($args);
    }
    public function onFlush(OnFlushEventArgs $args):void
    {
        return;
        $classes = ['App\Entity\Asset\Asset'];//, 'App\Entity\Organization\Tenant'];
        $rs=[];
        foreach($this->getOnFlushObjects($args, $classes) as $o) {
            //print_r($object->debug());
            $rs[] = $o->debug();
            //$this->printProperties($o);
            printf('createBy : %-20s updateBy : %-20s createAt : %-20s updateAt : %-20s'.PHP_EOL, gettype($o->getCreateBy()), gettype($o->getUpdateBy()), gettype($o->getCreateAt()), gettype($o->getUpdateAt()));
        }
        //echo(json_encode($rs).PHP_EOL);
        $this->logOnFlush($args);
    }
    public function postFlush(PostFlushEventArgs $args):void
    {
        $this->logOtherImportantActivity($args);
    }
    public function onClear(OnClearEventArgs $args):void
    {
        $this->logActivityWithClassName($args, $args->getEntityClass());
    }

    private function log(string $message): void
    {
        echo($message.PHP_EOL);
        $this->logger->info($message);
    }

    private function logPrePersist($args): void
    {
        return;
        $entity = $args->getObject();
        $this->log(sprintf('TestingActivitySubscriber %s(%s): %s %s'.PHP_EOL, debug_backtrace()[1]['function'], get_class($args), get_class($entity), $this->getIdentifier($entity)));
    }

    private function logActivity($args): void
    {
        return;
        $entity = $args->getObject();
        $this->log(sprintf('TestingActivitySubscriber %s(%s): %s %s'.PHP_EOL, debug_backtrace()[1]['function'], get_class($args), get_class($entity), $this->getIdentifier($entity)));
    }

    private function logActivityWithClassName($args, $classname): void
    {
        return;
        $this->log(sprintf('TestingActivitySubscriber %s(%s) %s'.PHP_EOL, debug_backtrace()[1]['function'], get_class($args), $classname));
    }

    private function logOtherImportantActivity($args): void
    {
        return;
        $this->log(sprintf('TestingActivitySubscriber %s(%s)'.PHP_EOL, debug_backtrace()[1]['function'], get_class($args)));
    }

    private function logOtherActivity($args): void
    {
        return;
        $this->log(sprintf('TestingActivitySubscriber %s(%s)'.PHP_EOL, debug_backtrace()[1]['function'], get_class($args)));
    }


    private function logOnFlush(OnFlushEventArgs $eventArgs): void
    {
        return;
        $this->log(debug_backtrace()[1]['function'].PHP_EOL);
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        $this->log('getScheduledEntityInsertions'.PHP_EOL);
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->log(sprintf('- %s %s'.PHP_EOL, get_class($entity), $this->getIdentifier($entity)));
        }

        $this->log('getScheduledEntityUpdates'.PHP_EOL);
        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->log(sprintf('- %s %s'.PHP_EOL, get_class($entity), $this->getIdentifier($entity)));
        }

        $this->log('getScheduledEntityDeletions'.PHP_EOL);
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->log(sprintf('- %s %s'.PHP_EOL, get_class($entity), $this->getIdentifier($entity)));
        }

        $this->log('getScheduledCollectionDeletions'.PHP_EOL);
        foreach ($uow->getScheduledCollectionDeletions() as $col) {
            $this->log(sprintf('- %s %s'.PHP_EOL, get_class($entity), $this->getIdentifier($entity)));
        }

        $this->log('getScheduledCollectionUpdates'.PHP_EOL);
        foreach ($uow->getScheduledCollectionUpdates() as $col) {
            $this->log(sprintf('- %s %s'.PHP_EOL, get_class($entity), $this->getIdentifier($entity)));
        }
    }
    private function getIdentifier($entity):mixed
    {
        return method_exists($entity, 'getId')?$entity->getId()??'NULL': 'UNKNOWN';
    }

    private function getOnFlushObjects(OnFlushEventArgs $eventArgs, array $classes):array
    {
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        $objects = [];
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if(in_array(get_class($entity), $classes)) {
                $objects[] = $entity;
            }
        }
        return $objects;
    }

    private function printProperties(object $obj):array
    {
        $reflect = new ReflectionClass($obj);
        $props = [];
        foreach ($reflect->getProperties() as $p) {
            printf('name: %-25s type: %-50s visability: %10s static: %s readOnly: %s default: %s promoted: %s'.PHP_EOL, $p->getName(), $p->getType(), $p->isPublic()?'public':($p->isProtected()?'protected':'private'), $p->isReadOnly()?'y':'n', $p->isDefault()?'y':'n', $p->isPromoted()?'y':'n', $p->isStatic()?'y':'n');
            $props[] = $p;
        }
        return $props;
    }
}