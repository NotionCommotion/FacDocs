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

namespace App\DataFixtures;

use PDO;
use App\Entity\Document\DocumentStage;
use App\Entity\Document\DocumentStageRankedList;
use App\Entity\Document\DocumentType;
use App\Entity\Document\DocumentTypeRankedList;
use App\Entity\Document\MediaType;
//use App\Entity\Document\MediaTypeRankedList;
use App\Entity\Project\ProjectStage;
use App\Entity\Project\ProjectStageRankedList;
use App\Entity\Project\Project;
use App\Entity\Specification\CsiSpecification;
use App\Entity\Specification\NaicsCode;
use App\Entity\User\Department;
use App\Entity\User\DepartmentRankedList;
use App\Entity\User\JobTitle;
use App\Entity\User\JobTitleRankedList;
use App\Entity\User\TenantUser;
use App\Entity\User\VendorUser;
use App\Entity\User\SystemUser;
use App\Entity\User\UserInterface;
use App\Entity\Acl\Role;
use App\Entity\HelpDesk\Status;
use App\Entity\Organization\OrganizationInterface;
use App\Entity\Organization\SystemOrganization;
use App\Entity\Organization\Tenant;
use App\Entity\Organization\TestingTenant;
use App\Entity\Organization\Vendor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Uid\NilUlid;
use Gedmo\Blameable\BlameableListener;
use Exception;
use InvalidArgumentException;
use DateTime;
use SplObjectStorage;

class AppFixtures extends Fixture
{
    private const SYSTEM_ORGANIZATION_NAME = 'SYSTEM';
    private const TESTING_TENANT_NAME    = 'TestingTenant';
    private const TESTING_VENDOR_NAME    = 'TestingVendor';
    private const PASSWORD = 'testing';
    private const USER_FIRST_NAME = '_TESTER_';
    // t_media_type_type and t_us_state are not managed by doctrine and is just used to enforce foreign constaints
    private const MEDIA_TYPE_TYPE_TABLE = 't_media_type_type';
    private const US_STATE_TABLE = 't_us_state';
    private const USERS_MANUAL_TABLE = 'user_manual';
    private string $rootId;

    private SplObjectStorage $passwordMap;
    
    // User's last name will be their declared role.

    public function __construct(private string $sourceData, private array $roles, private BlameableListener $blameableListener)
    {
        $this->passwordMap = new SplObjectStorage();
        $this->rootId = (new NilUlid)->toRfc4122();
    }

    public function load(ObjectManager $objectManager): void
    {
        //$this->addForeignConstraint($objectManager, 'project', 'acl', 'id', 'id', 'project_acl_pk');
        echo('Create non-tenant resources:'.PHP_EOL);
        $specHelper = (new SpecHelper((new CsiSpecification())->setTitle('root')->setDivision('')));
        $this
        // ->prepareUserManual($objectManager)
        ->installStringIdDefault($objectManager, DocumentStage::class, 'document-stage.csv')
        ->installStringIdDefault($objectManager, ProjectStage::class, 'project-stage.csv')
        ->installStringIdDefault($objectManager, DocumentType::class, 'document-type.csv')
        ->installIntIdDefault($objectManager, Department::class, 'departments.csv')
        ->installIntIdDefault($objectManager, JobTitle::class, 'job-titles.csv')
        ->installAccessRoles($objectManager)
        ->installStringIdDescription($objectManager, Status::class, 'help-desk-status.csv')
        ->installMediaTypes($objectManager, 'media-types.json', 'media-types.csv', 'default-media.csv')
        ->installUsStates($objectManager, 'us-states.json')
        ->installNaicsCode($objectManager, 'naics-2_6_digit-2022.csv');

        $this->installSpecifications($objectManager, 'masterFormat_2016.csv', $specHelper);

        echo('Create ROOT user:'.PHP_EOL);
        $rootUser = $this->createRootUser($objectManager);
        $systemOrganization = $rootUser->getRealOrganization();
        $rootUser->setTenant($systemOrganization);
        $this->blameableListener->setUserValue($rootUser);

        // Update password which wasn't flushed.
        $rootPassword = 'wordpass';
        $this->passwordMap[$rootUser] = $rootPassword;
        $rootUser->setPlainPassword($rootPassword)->setUsername('NotionCommotion'); //Change something for flush to take since planPassword is not managed by Doctrine.
        $objectManager->persist($rootUser);

        // Hack for vender users.  See TempHackProvider
        $this->changeNullableConstraint($objectManager, 'public.user', 'organization_id', false);
        $this->addSystemTestingUsers($objectManager, $rootUser);
        $objectManager->persist($systemOrganization);
        $objectManager->flush();
        //$this->persistUsers($systemOrganization, $objectManager);

        $testingTenant = $this->createTestingTenant($objectManager, $rootUser);
        $this->addTenantTestingUsers($objectManager, $rootUser, $testingTenant);
        $objectManager->persist($testingTenant);
        //$this->persistUsers($testingTenant, $objectManager);

        try {
            $objectManager->flush();
        } catch (Exception $e) {
            echo('catch! '.$e->getMessage().PHP_EOL);
            // Delete anything done with native SQL using native SQL
            $conn = $objectManager->getConnection();
            // Could $conn->rollback() be used?
            echo(get_class($conn).PHP_EOL);
            
            $conn->prepare('DELETE FROM organization WHERE id=?')->executeStatement([$this->rootId]);

            $conn->exec('DELETE FROM '.self::USERS_MANUAL_TABLE);
            $conn->exec('DELETE FROM '.self::US_STATE_TABLE);
            $conn->exec('DELETE FROM '.self::MEDIA_TYPE_TYPE_TABLE);
            echo('throw $e'.PHP_EOL);
            
            throw $e;
        }
        $this->changeNullableConstraint($objectManager, 'public.user', 'organization_id', true);

        $this->validateSpecifications($objectManager, $specHelper);

        $this->displayUserCredentials($systemOrganization)->displayUserCredentials($testingTenant);
        foreach($testingTenant->getVendors() as $vendor) {
            $this->displayUserCredentials($vendor);
        }
        
        echo 'Complete!'.\PHP_EOL;
    }

    private function installAccessRoles(ObjectManager $objectManager): self
    {
        $roles=$this->roles;
        foreach($roles as $role=>$inheritedRoles) {
            $objectManager->persist(new Role($role));
            foreach($inheritedRoles as $inheritedRole) {
                if(!isset($roles[$inheritedRole])) {
                    $roles[$inheritedRole] = [];
                    $objectManager->persist(new Role($inheritedRole));
                }
            }
        }
        $objectManager->flush();
        return $this;
    }

    private function prepareUserManual(ObjectManager $objectManager): self
    {
        // Create a fake page to be used as roots parent.
        $conn = $objectManager->getConnection();
        $conn->beginTransaction();
        $sql = sprintf('INSERT INTO %s (id, parent_id, topic, name, content, list_order, display_list, keywords) VALUES (:id, :parent_id, :topic, :name, :content, :list_order, :display_list, :keywords)', self::USERS_MANUAL_TABLE);
        $stmt = $conn->prepare($sql);

        $d = [
            'id' => 0,
            'parent_id' => 0,
            'topic' => '',
            'name' => 'notused',
            'content' => null,
            'list_order' => 0,
            'display_list' => 0,
            'keywords' => null,
        ];
        $stmt->execute($d);

        $d = [
            'id' => 1,
            'parent_id' => 0,
            'topic' => 'root',
            'name' => 'root',
            'content' => 'root',
            'list_order' => 0,
            'display_list' => 1,
            'keywords' => null,
        ];
        $stmt->execute($d);
        $conn->commit();

        return $this;
    }

    private function changeNullableConstraint(ObjectManager $objectManager, string $table, string $column, bool $add)
    {
        return $objectManager->getConnection()->exec(sprintf('ALTER TABLE %s ALTER COLUMN %s %s NOT NULL', $table, $column, $add?'SET':'DROP'));
    }

    private function alterDeferable(Connection $conn, string $table, string $refenceTable, ?bool $add): self
    {
        $t = is_null($add)?'NOT DEFERRABLE':($add?'DEFERRABLE INITIALLY DEFERRED':'DEFERRABLE INITIALLY IMMEDIATE');
        foreach($conn->getSchemaManager()->listTableForeignKeys($table) as $foreignKey){
            if($foreignKey->getForeignTableName()===$refenceTable) {
                $conn->query(sprintf('ALTER TABLE public.%s ALTER CONSTRAINT %s %s'.PHP_EOL, $table, $foreignKey->getName(), $t));
            }
        }
        return $this;
    }
    private function createInsertQuery(string $table, array $columns, bool $placeholders=true): string
    {
        return sprintf('INSERT INTO %s(%s) VALUES(%s)', $table, implode(',', $columns), $placeholders?':'.implode(',:', $columns):implode(',', array_fill(0, count($columns), '?')));
    }

    private function insertRow(string $table, array $data, PDO $pdo): self
    {
        $sql = $this->createInsertQuery($table, array_keys($data));
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
        return $this;
    }

    private function persistUsers(OrganizationInterface $organization, ObjectManager $objectManager):self
    {
        foreach($organization->getUsers() as $user) {
            $objectManager->persist($user);
        }
        return $this;
    }

    private function displayUserCredentials(OrganizationInterface $organization):self
    {
        foreach($organization->getUsers() as $user) {
            echo($user->getLogon($this->passwordMap[$user], true).PHP_EOL);
        }
        return $this;
    }

    private function createRootUser(ObjectManager $objectManager): SystemUser
    {
        $rootUserId = $this->rootId;
        $organizationId = $this->rootId;
        $datetime = (new DateTime)->format('Y-m-d H:i:s');

        $primarySpecification = $objectManager->getRepository(CsiSpecification::class)->findOneBy(['division'=>'00', 'section'=>'00', 'scope'=>'00']);

        $conn = $objectManager->getConnection();
        try {
            // Make constraints deferable to prevent foreign key constraint errors.
            $this->alterDeferable($conn, 'organization', 'user', true);
            $this->alterDeferable($conn, 'user', 'user', true);
            $conn->commit();

            // Go native to just get the root system and user complete as deferred indexes are required.
            $pdo = $conn->getNativeConnection();
            $pdo->beginTransaction();

            $arr = ['id'=>$organizationId, 'discriminator'=>'system', 'name'=>self::SYSTEM_ORGANIZATION_NAME, 'create_at'=>$datetime, 'update_at'=>$datetime, 'create_by_id'=>$rootUserId, 'update_by_id'=>$rootUserId, 'primary_specification_id'=>$primarySpecification->getId()->toRfc4122()];
            $this->insertRow('public.organization', $arr, $pdo);
            $pdo->prepare('INSERT INTO public.system_organization(id) VALUES(?)')->execute([$organizationId]);

            $arr = array_merge(array_intersect_key($arr, array_flip(['create_by_id', 'update_by_id', 'create_at', 'update_at', 'discriminator'])), ['id'=>$rootUserId,'organization_id'=>$organizationId, 'first_name'=>'Michael', 'last_name'=>'Reed', 'username'=>'villascape@gmail.com', 'email'=>'villascape@gmail.com', 'password'=>'replace me', 'roles'=>'["ROLE_SYSTEM_SUPER"]']);
            $this->insertRow('public.user', $arr, $pdo);
            $pdo->prepare('INSERT INTO public.system_user(id) VALUES(?)')->execute([$rootUserId]);

            $pdo->commit();
        } catch (Exception $e) {
            echo('createRootUser catch! '.$e->getMessage().PHP_EOL);
        }

        // Set constraints back.
        $this->alterDeferable($conn, 'organization', 'user', null);
        $this->alterDeferable($conn, 'user', 'user', null);

        // Go back with using Doctrin's connection.
        $conn->beginTransaction();
        return $objectManager->getRepository(SystemUser::class)->find($rootUserId);
    }

    private function createTestingTenant(ObjectManager $objectManager, SystemUser $rootUser): TestingTenant
    {
        return (new TestingTenant)->setName(self::TESTING_TENANT_NAME);
    }

    private function addSystemTestingUsers(ObjectManager $objectManager, SystemUser $rootUser): void
    {
        $systemOrganization = $rootUser->getRealOrganization();
        foreach([
            ['email' =>'test.system.admin@example.com', 'lastName' =>'ROLE_SYSTEM_ADMIN', 'roles' =>['ROLE_SYSTEM_ADMIN']],
            ['email' =>'test.system.user@example.com', 'lastName' =>'ROLE_SYSTEM_USER',  'roles' =>['ROLE_SYSTEM_USER']],
            ['email' =>'test.system.base@example.com', 'lastName' =>'ROLE_USER',  'roles' =>[],],
            ] as $arr)
        {
            $systemOrganization->addUser($this->updateUser((new SystemUser())->setOrganization($systemOrganization)->impersonate($systemOrganization), $arr, $objectManager));
        }
    }

    private function addTenantTestingUsers(ObjectManager $objectManager, SystemUser $rootUser, TestingTenant $tenant): void
    {
        foreach([
            ['email' =>'test.tenant.admin@example.com', 'lastName' =>'ROLE_TENANT_ADMIN', 'roles' =>['ROLE_TENANT_ADMIN'],],
            ['email' =>'test.tenant.user@example.com', 'lastName' =>'ROLE_TENANT_USER',  'roles' =>['ROLE_TENANT_USER'],],
            ['email' =>'test.tenant.base@example.com', 'lastName' =>'ROLE_USER',  'roles' =>[],],
            ] as $arr)
        {
            $tenant->addUser($this->updateUser((new TenantUser())->setTenant($tenant), $arr, $objectManager));
        }

        $vendor = (new Vendor)->setName(self::TESTING_VENDOR_NAME);
        $tenant->addVendor($vendor);

        foreach([
            ['email' =>'test.vendor.admin@example.com', 'lastName' =>'ROLE_VENDOR_ADMIN', 'roles' =>['ROLE_VENDOR_ADMIN'],],
            ['email' =>'test.vendor.user@example.com', 'lastName' =>'ROLE_VENDOR_USER',  'roles' =>['ROLE_VENDOR_USER'],],
            ['email' =>'test.vendor.base@example.com', 'lastName' =>'ROLE_USER',  'roles' =>[],],
            ] as $arr)
        {
            $vendor->addUser($this->updateUser((new VendorUser())->setOrganization($vendor), $arr, $objectManager));
        }
    }

    private function updateUser($user, $arr, $objectManager):UserInterface
    {
        $arr['password'] = $arr['password']??self::PASSWORD;
        $user
        ->setEmail($arr['email'])->setUsername($arr['email'])
        ->setPlainPassword($arr['password'])
        ->setRoles($arr['roles'])
        ->setFirstName($arr['firstName']??self::USER_FIRST_NAME)->setLastName($arr['lastName']);
        $this->passwordMap[$user] = $arr['password'];
        return $user;
    }

    private function installStringIdDefault(ObjectManager $objectManager, string $class, string $file): self
    {
        $items = $this->parseCsvFile($this->sourceData.'/'.$file);
        printf('Install %d records for %s'.\PHP_EOL, \count($items), $class);
        $rankedListClass = match ($class) {
            DocumentStage::class => new DocumentStageRankedList(),
            DocumentType::class => new DocumentTypeRankedList(),
            ProjectStage::class => new ProjectStageRankedList(),
            default => throw new Exception(sprintf('installStringIdDefault() does not support class %s', $class)),
        };
        foreach ($items as $item) {
            $rankedList = (new $rankedListClass())->setDefaultRanking(isset($item['ranking']) ? (int) $item['ranking'] : null);
            $objectManager->persist((new $class($rankedList))->setIdentifier($item['id'])->setName($item['name'] ?? $item['id']));
        }
        //$objectManager->flush();

        return $this;
    }

    private function installIntIdDefault(ObjectManager $objectManager, string $class, string $file): self
    {
        $items = $this->parseCsvFile($this->sourceData.'/'.$file);
        printf('Install %d records for %s'.\PHP_EOL, \count($items), $class);
        $rankedListClass = match ($class) {
            Department::class => new DepartmentRankedList(),
            JobTitle::class => new JobTitleRankedList(),
            default => throw new Exception(sprintf('installIntIdDefault() does not support class %s', $class)),
        };
        foreach ($items as $item) {
            $rankedList = (new $rankedListClass())->setDefaultRanking(isset($item['ranking']) ? (int) $item['ranking'] : null);
            $objectManager->persist((new $class($rankedList))->setName($item['name']));
        }
        //$objectManager->flush();

        return $this;
    }

    private function installStringIdDescription(ObjectManager $objectManager, string $class, string $file): self
    {
        $items = $this->parseCsvFile($this->sourceData.'/'.$file);
        printf('Install %d records for %s'.\PHP_EOL, \count($items), $class);
        foreach ($items as $item) {
            $objectManager->persist((new $class($item['name'] ?? $item['id']))->setDescription($item['description']));
        }
        //$objectManager->flush();

        return $this;
    }

    private function installMediaTypes(ObjectManager $objectManager, string $jsonFile, string $csvFile, string $listRanking): self
    {
        echo 'Install Media Types'.\PHP_EOL;

        // Not used?
        $this->parseCsvFile($this->sourceData.'/'.$listRanking);

        $this->addMediaTypeType($objectManager);

        $db = json_decode(file_get_contents($this->sourceData.'/'.$jsonFile), true, 512, JSON_THROW_ON_ERROR);

        $csvFile = $this->sourceData.'/'.$csvFile;
        if (($handle = fopen($csvFile, 'r')) === false) {
            throw new InvalidArgumentException('Cannot open '.$csvFile);
        }
        $header = null;
        $data = [];
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if ($header) {
                $row = array_combine($header, $row);
                $data[$row['Type']][] = $row;
            } else {
                $header = $row;
            }
        }

        $example = [];
        $ignore = [];
        foreach ($data as $type => $items) {
            foreach ($items as $item) {
                if ('example' === $item['Name']) {
                    $example[] = $item['Template'];
                    continue;
                }
                $template = strtolower(empty($item['Template']) ? sprintf('%s/%s', $item['Type'], $item['Name']) : $item['Template']);
                if (!isset($db[$template])) {
                    $ignore[] = $item;
                    continue;
                }
                $name = $item['Template'] ?: $item['Name'];
                $mediaType = (new MediaType($template))
                ->setType($type)
                // Can't have dot in route.  Figure out. https://github.com/api-platform/api-platform/issues/1557
                ->setSubtype(str_replace('.', '-', explode('/', $template)[1]))
                ->setName($name)
                ->setReference($item['Reference'])
                ->setSupportedExtensions($db[$template]['extensions'] ?? [])
                ->setCompressible(isset($db[$template]['compressible']) ? (bool) ($db[$template]['compressible']) : null);
                $objectManager->persist($mediaType);
            }
        }
        printf('Following are just examples and are not included: %s'.\PHP_EOL, implode(', ', $example));

        if ($ignore !== []) {
            echo 'Following are are not found and are ignored:'.\PHP_EOL;
            print_r($ignore);
        }
        //$objectManager->flush();

        return $this;
    }

    private function addMediaTypeType(ObjectManager $objectManager): self
    {
        $table = self::MEDIA_TYPE_TYPE_TABLE;
        $conn = $objectManager->getConnection();
        $sql = "CREATE TABLE IF NOT EXISTS $table (type varchar(16) NOT NULL UNIQUE, PRIMARY KEY (type))";
        $conn->exec($sql);
        $sql = 'SELECT type FROM '.$table;
        if ($types = array_diff(['application', 'audio', 'font', 'image', 'message', 'model', 'multipart', 'text', 'video'], $conn->query($sql)->fetchAll(PDO::FETCH_COLUMN))) {
            $sql = sprintf('INSERT INTO %s(type) VALUES %s', $table, "('".implode("'), ('", $types)."')");
            $conn->exec($sql);
        }
        $this->addForeignConstraint($objectManager, 'media_type', $table, 'type', 'type', 'fk_media_type_type');

        return $this;
    }

    private function installUsStates(ObjectManager $objectManager, string $file): self
    {
        $table = self::US_STATE_TABLE;
        $conn = $objectManager->getConnection();
        $sql = "CREATE TABLE IF NOT EXISTS $table (id char(2) NOT NULL UNIQUE, name varchar(32) NOT NULL UNIQUE, PRIMARY KEY (id))";
        $conn->exec($sql);

        $sql = 'SELECT id FROM '.$table;
        if ($states = array_diff_key(json_decode(file_get_contents($this->sourceData.'/'.$file), true, 512, JSON_THROW_ON_ERROR), array_flip($conn->query($sql)->fetchAll(PDO::FETCH_COLUMN)))) {
            $rows = [];
            foreach ($states as $id => $name) {
                $rows[] = "('$id', '$name')";
            }
            $sql = sprintf('INSERT INTO %s(id, name) VALUES %s', $table, implode(',', $rows));
            $conn->exec($sql);
        }
        //string $table, string $referenceTable, string $column, string $referenceColumn, string $fkName
        $this->addForeignConstraint($objectManager, 'organization', $table, 'location_state', 'id', 'fk_organization_us_state');
        $this->addForeignConstraint($objectManager, 'project', $table, 'location_state', 'id', 'fk_project_us_state');
        $this->addForeignConstraint($objectManager, 'asset', $table, 'location_state', 'id', 'fk_asset_us_state');

        return $this;
    }

    private function installNaicsCode(ObjectManager $objectManager, string $file): self
    {
        echo 'Install NaicsCode'.\PHP_EOL;

        $parents=[];
        $rows = array_map('str_getcsv', file($this->sourceData.'/'.$file));
        foreach ($rows as $row) {
            if (ctype_digit($row[0])) {
                if (!mb_check_encoding($row[2])) {
                    throw new \Exception("Not valid character: $code ($row[2])");
                }
                $multiple=explode('-', (string) $row[1]);
                switch(count($multiple)) {
                    case 1:
                        if (!ctype_digit($row[1])) {
                            throw new \Exception("NAIC Code must be a digit: $row[1]");
                        }
                        switch(strlen((string) $row[1])) {
                            case 2:
                                $parent=null;
                                $type='sector';
                                break;
                            case 3:
                                $parent=$parents[substr((string) $row[1], 0, 2)];
                                $type='subsector';
                                break;
                            case 4:
                                $parent=$parents[substr((string) $row[1], 0, 3)];
                                $type='industry-group';
                                break;
                            case 5:
                                $parent=$parents[substr((string) $row[1], 0, 4)];
                                $type='industry';
                                break;
                            case 6:
                                $parent=$parents[substr((string) $row[1], 0, 5)];
                                $type='national-industry';
                                break;
                            default: throw new \Exception("NAIC Code must be 2 to 6 digits: $row[1]");
                        }
                        $naicsCode = new NaicsCode();
                        $parents[$row[1]] = $naicsCode;
                        $objectManager->persist($naicsCode->setCode($row[1])->setTitle($row[2])->setParent($parent)->setType($type));
                        break;
                    case 2:
                        $type='Sector';
                        for ($c = $multiple[0]; $c <= $multiple[1]; $c++) {
                            $code = (string) $c;
                            if (!ctype_digit($code)) {
                                throw new \Exception("NAIC Code must be a digit: $code");
                            }

                            if(strlen($code)===2) {
                                $naicsCode = new NaicsCode();
                                $parents[$code] = $naicsCode;
                                $objectManager->persist($naicsCode->setCode($code)->setTitle($row[2])->setType($type));
                            }
                            else {
                                throw new \Exception("Secotor NAIC Code must be 2 digits: $code ($row[1])");
                            }
                        }
                        break;
                    default: throw new \Exception("Invalid multiple NAIC Codes: $row[1]");

                }
            } else {
                printf('No insert for:   code: %s name: %s'.\PHP_EOL, $row[0], $row[1]);
            }
        }
        //$objectManager->flush();

        return $this;
    }

    private function installSpecifications(ObjectManager $objectManager, string $file, SpecHelper $specHelper): self
    {
        echo 'Create CSI Specifications'.\PHP_EOL;
        $objectManager->flush();
        foreach ($this->parseSpecCsvFile($this->sourceData.'/'.$file) as $spec) {
            SpecHelper::create($spec[1], $spec[0], $specHelper);
        }
        // $root->debug();

        echo 'Set Spec Parents'.\PHP_EOL;
        $log = $specHelper->setSpecParents();
        $this
        ->displayLog('Parent not set for the following divisions', $log['division'])
        ->displayLog("Parent's spec replaced with parent's parent spec", $log['emptyParent'])
        ->displayLog('Parent not set since the child is empty', $log['emptyChild']);

        echo 'Save CSI Specifications'.\PHP_EOL;
        $specHelper->persist($objectManager);
        $objectManager->flush();
        return $this;
    }
    private function validateSpecifications(ObjectManager $objectManager, SpecHelper $specHelper)
    {
        echo 'Validate Parent'.\PHP_EOL;
        $q = $objectManager->createQuery("SELECT cs FROM App\Entity\Specification\CsiSpecification cs WHERE cs.parent IS NULL AND (cs.section!='00' OR cs.scope!='00' OR cs.subscope IS NOT NULL)");
        $errors = $q->getResult();
        if ((is_countable($errors) ? \count($errors) : 0) > 0) {
            echo sprintf('Excecution cancelled.  There are %d specifications entities without a parent'.\PHP_EOL, is_countable($errors) ? \count($errors) : 0);
            foreach ($errors as $error) {
                printf('%15s %s'.\PHP_EOL, $error->getSpec(), $error->getName());
            }
            throw new \Exception('installSpecifications error?');
        }
        echo 'Success.  All specifications entities have a parent'.\PHP_EOL;

        echo 'Validate Specifications Count'.\PHP_EOL;
        $objectRepository = $objectManager->getRepository(CsiSpecification::class);
        $entities = $objectRepository->findAll();
        $dbCount = \count($entities);
        $count = $specHelper->getCount();
        if ($dbCount !== $count) {
            throw new \Exception(sprintf('Excecution cancelled.  DB has %d specifications rows and not %d rows'.\PHP_EOL, $dbCount, $count));
        }
        echo sprintf('Success.  There are a total of %d specifications records'.\PHP_EOL, $dbCount);

        return $this;
    }

    private function trimArray(array $row, int ...$excludeCodes): array
    {
        return array_map(function ($v) use ($excludeCodes) {
            if (!mb_check_encoding($v)) {
                $orig = $v;
                $l = \strlen($v);
                for ($i = 0; $i < $l; ++$i) {
                    if (!mb_check_encoding($v[$i])) {
                        $ord = \ord($v[$i]);
                        if (!\in_array($ord, $excludeCodes, true)) {
                            $v[$i] = match ($ord) {
                                150 => '-',
                                231 => 'c',
                                default => throw new Exception(sprintf('"%s" with code %s in string %s is not valid UTF-8', $v[$i], $ord, $v)),
                            };
                        }
                    }
                }
                if ($v !== $orig) {
                    printf('String "%s" was changed to "%s"'.\PHP_EOL, $orig, $v);
                }
            }

            return trim($v);
            }, $row);
    }

    private function parseCsvFile(string $csvFile): array
    {
        if (($handle = fopen($csvFile, 'r')) === false) {
            throw new InvalidArgumentException('Cannot open '.$csvFile);
        }
        $header = array_map('trim', fgetcsv($handle, 0, ','));
        $results = [];
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $results[] = array_combine($header, $this->trimArray($row));
        }
        fclose($handle);

        return $results;
    }

    private function parseSpecCsvFile(string $csvFile): array
    {
        if (($handle = fopen($csvFile, 'r')) === false) {
            throw new InvalidArgumentException('Cannot open '.$csvFile);
        }
        $row = -1;
        $results = [];
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $data = $this->trimArray($data, ...[174]); // Exclude copywrite symbol
            ++$row;
            if (4 !== \count($data)) {
                throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
            }
            if (!$data[0] && !$data[1] && !$data[2] && !$data[3]) {
                // empty
                $results[$row] = null;
                continue;
            }
            if (array_filter($data, fn($v) => str_contains((string) $v, 'MasterFormat')) && array_filter($data, fn($v) => 'April 2016' === $v)) {
                // master
                $results[$row] = null;
                continue;
            }

            if ($data[3]) {
                throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
            }

            $arr = array_values(array_filter($data, fn($v) => '' !== $v));
            if (1 === \count($arr)) {
                $arr = reset($arr);
                if (ctype_digit($arr)) {
                    // pagenumber
                    $results[$row] = null;
                } else {
                    // overflow
                    $counter = 1;
                    while (null === $results[$row - $counter]) {
                        ++$counter;
                    }
                    $results[$row - $counter][1] .= ' '.$arr;
                }
                continue;
            }

            $parts = explode(' ', (string) $arr[0]);
            if (\count($parts) > 1) {
                if (3 === \count($parts)) {
                    if (2 !== \count($arr)) {
                        throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
                    }
                    foreach ($parts as $part) {
                        if (!ctype_digit($part)) {
                            throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
                        }
                    }
                    // mainsection
                    $results[$row] = [$arr[0], $arr[1]];
                    continue;
                }
                if (2 === \count($parts)) {
                    if (3 !== \count($arr)) {
                        throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
                    }
                    foreach ($parts as $part) {
                        if (!ctype_digit($part)) {
                            throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
                        }
                    }
                    if (!ctype_digit($arr[1])) {
                        throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
                    }
                    // mainsection
                    $results[$row] = [$arr[0].' '.$arr[1], $arr[2]];
                    continue;
                }
                throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
            }

            $parts = explode('.', (string) $arr[0]);
            if (\count($parts) > 1) {
                if (2 !== \count($arr)) {
                    throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
                }
                foreach ($parts as $part) {
                    if (!ctype_digit($part)) {
                        throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
                    }
                }
                // subsection
                $results[$row] = [$arr[0], $arr[1]];
                continue;
            }

            if (2 === \count($arr) && ctype_digit($arr[0]) && $arr[1] && !ctype_digit($arr[1])) {
                // fix data
                $results[$row] = [$arr[0], $arr[1]];
                continue;
            }
            if (3 === \count($arr) && ctype_digit($arr[0]) && is_numeric($arr[1]) && $arr[2] && !ctype_digit($arr[2])) {
                // fix data
                if (!ctype_digit($arr[1])) {
                    $t = explode('.', $arr[1]);
                    $arr[1] = sprintf('%s.%s', str_pad($t[0], 4, '0', \STR_PAD_LEFT), str_pad($t[1], 2, '0', \STR_PAD_LEFT));
                }
                $results[$row] = [$arr[0].' '.$arr[1], $arr[2]];
                continue;
            }

            throw new Exception(sprintf('error parsing spec: %s', json_encode($data, JSON_THROW_ON_ERROR)));
        }
        fclose($handle);

        return array_values(array_filter($results, fn($v) => null !== $v));
    }

    private function addForeignConstraint(ObjectManager $objectManager, string $table, string $referenceTable, string $column, string $referenceColumn, string $fkName): self
    {
        $sql = <<<EOT
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = '$fkName') THEN
        ALTER TABLE $table
            ADD CONSTRAINT $fkName
            FOREIGN KEY ($column) REFERENCES $referenceTable($referenceColumn);
    END IF;
END;
$$;
EOT;
        printf('Add FK %s %s.%s references %s.%s'.PHP_EOL, $fkName, $table, $column, $referenceTable, $referenceColumn);
        $objectManager->getConnection()->exec($sql);

        return $this;
    }

    private function addForeignConstraints(ObjectManager $manager, array $tables, string $referenceTable, string $column, string $referenceColumn): self
    {
        foreach ($tables as $table) {
            $this->addForeignConstraint($manager, $table, $referenceTable, $column, $referenceColumn, sprintf('%s_%s_fkey', $table, $referenceTable));
        }

        return $this;
    }


    private function displayLog(string $title, array $rows): self
    {
        printf('%s (quantity of %d)'.\PHP_EOL, $title, \count($rows));
        foreach ($rows as $row) {
            echo '  '.$row.\PHP_EOL;
        }

        return $this;
    }
}
