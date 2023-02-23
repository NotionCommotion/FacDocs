<?php declare(strict_types=1);

namespace App\DataFixtures\Purger;

use Doctrine\Persistence\ObjectManager;
use Fidry\AliceDataFixtures\Persistence\PurgeMode;
use Fidry\AliceDataFixtures\Persistence\PurgerFactoryInterface;
use Fidry\AliceDataFixtures\Persistence\PurgerInterface;
use Nelmio\Alice\IsAServiceTrait;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\NilUlid;
use App\Entity\Organization\Tenant;
use App\Entity\Organization\AbstractOrganization;
use App\Entity\Organization\Vendor;
use App\Entity\User\SystemUser;

# Used for hacks!
use Gedmo\Blameable\BlameableListener;
use Faker\Generator;
use App\Entity\Organization\SystemOrganization;

/**
 * @final
 */
/* final */ class AlicePurger implements PurgerInterface, PurgerFactoryInterface
{
    use IsAServiceTrait;

    public function __construct(private ObjectManager $manager, Generator $fakerGenerator, BlameableListener $blameableListener, PurgeMode $purgeMode = null)
    {
        $this->constructHacks($fakerGenerator, $blameableListener);
    }
    public function __destruct()
    {
        $this->destructHacks();
    }

    /**
     * Creates a new purger with the given purger mode. As the purger is stateful, it may be useful sometimes to create
     * a new purger with the same state as an existing one and just have control on the purge mode.
     */
    public function create(PurgeMode $mode, PurgerInterface $purger = null): PurgerInterface
    {
        return $this;        
    }


    /**
     * Purges the database before loading. Depending of the implementation, the purge may truncate the database or
     * remove only a part of the database data.
     */
    public function purge(): void
    {
        $this
        ->setAll(Tenant::class, ['createBy', 'updateBy'], new NilUlid)
        ->setAll(Vendor::class, ['createBy', 'updateBy'], new NilUlid)
        ->deleteAll(Tenant::class)
        ->deleteAll(Vendor::class)
        //->deleteAll(SystemUser::class)
        ->deleteNonRootSystemUsers()
        ;
    }

    private function deleteAll(string $class):self
    {
        $this->manager->createQuery(sprintf('DELETE FROM %s', $class))->execute();
        return $this;
    }

    private function setAll(string $class, array $properties, $value):self
    {
        $query = $this->manager->createQueryBuilder()->update($class, 'o');
        foreach($properties as $property) {
            $query->set('o.'.$property, ':'.$property);
            $query->setParameter($property, $value, $value instanceof Ulid?'ulid':null);
        }
        $query->getQuery()->execute();
        return $this;
    }

    private function deleteNonRootSystemUsers():self
    {
        $this->manager->createQuery(sprintf('DELETE %s su WHERE su.id != :id', SystemUser::class))->setParameter('id', new NilUlid, 'ulid')->execute();
        //$this->manager->flush();
        return $this;
    }

    private function constructHacks(Generator $fakerGenerator, BlameableListener $blameableListener)
    {
		// Temporary hack to deal with not being able to use a listner to get user from security context.
        $blameableListener->setUserValue($this->manager->getRepository(SystemOrganization::class)->find(new NilUlid)->getRootUser());

        // hack to seed the generator
        $fakerGenerator->seed(time());

        // Temporary hack to deal Doctrine commit issues.  See https://github.com/doctrine/orm/discussions/10026.
        // The constraint on tenant_id was only needed for TestingTenant.
        $this->changeNullableConstraint('public.user', 'organization_id', false);
        $this->changeNullableConstraint('vendor_user', 'tenant_id', false);
        $this->changeNullableConstraint('custom_specification', 'tenant_id', false);
    }
    private function destructHacks()
    {
        $errors = array_filter([
			$this->restoreNullableConstraint('public.user', 'organization_id'),
			$this->restoreNullableConstraint('vendor_user', 'tenant_id'),
			$this->restoreNullableConstraint('custom_specification', 'tenant_id'),
		]);
		if($errors) {
			throw new \Exception('NULL values in DB: '.implode(', ', $errors));
		}
    }
    private function changeNullableConstraint(string $table, string $column, bool $add)
    {
        return $this->manager->getConnection()->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s %s NOT NULL', $table, $column, $add?'SET':'DROP'));
    }
    private function restoreNullableConstraint(string $table, string $column):?string
    {
        if($errors = $this->manager->getConnection()->query(sprintf('SELECT id FROM %s WHERE %s IS NULL', $table, $column))->fetchAll(\PDO::FETCH_COLUMN)) {
			print_r($errors);
			echo($table.' '.implode(', ', $errors).PHP_EOL);
			$this->manager->getConnection()->exec(sprintf('DELETE FROM %s WHERE %s IS NULL', $table, $column));
		}
        $this->changeNullableConstraint($table, $column, true);
		return $errors?$table.': '.implode(', ', $errors):null;
    }
}