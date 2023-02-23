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

namespace App\Command;

use DateTimeZone;
use Exception;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Gedmo\Blameable\BlameableListener;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberUtil;
use App\Entity\Organization\Tenant;
use App\Entity\User\TenantUser;

#[AsCommand(name: 'app:add-tenant')]
final class AddTenant extends AbstractAddUser
{
    public function __construct(EntityManagerInterface $entityManager, PhoneNumberUtil $phoneNumberUtil, ValidatorInterface $validator, private BlameableListener $blameableListener, private array $testingTenant)
    {
        parent::__construct($entityManager, $phoneNumberUtil, $validator, $blameableListener);
    }

    protected function configure(): void
    {
        $this
        ->setDescription('SRS Tenant Creater.  --help')
        ->setHelp('This command allows you to create a SRS tenant')
        ->addOption('existing', null, null, 'Provide to add user to existing tenant');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tenant = $this->testingTenant;
        $tenantUser = $tenant['users'][0];
        unset($tenant['users']);

        new SymfonyStyle($input, $output);
        $output->writeln([
            'Tenant and Tenant User Creater',
            '============',
            '',
        ]);

        $helper = $this->getHelper('question');

        $this->setBlamable($helper, $input, $output);

        $tenant =$input->getOption('existing')
        ?$this->getExistingTenant($helper, $input, $output)
        :$this->createTenant(new Tenant(), $this->getTenantData($tenant, $helper, $input, $output));

        $tenantUser = $this->createUser((new TenantUser())->setTenant($tenant), $this->getUserData($tenantUser, $helper, $input, $output));

        $output->writeln('');
        $output->writeln('Following values will be used.');
        $this->displayTenantData($tenant, $output);
        $this->displayUserData($tenantUser, $output);

        $question = new ConfirmationQuestion('Continue with this action?', true);
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $output->writeln('');

        if($error = $this->failsValidation($tenant, $tenantUser, $output)) {
            return $error;
        }

        $password = $tenantUser->getPlainPassword();

        $this->entityManager->persist($tenant);
        $this->entityManager->persist($tenantUser);
        try {
            $this->entityManager->flush();
        } catch (Exception $exception) {
            $output->writeln($exception->getMessage());
            return Command::FAILURE;
        }

        $output->writeln('Tenant successfully generated!');
        $output->writeln('Tenant ID: '.$tenant->getId()->toRfc4122());
        $output->writeln('USER ID: '.$tenantUser->getId()->toRfc4122());
        $output->writeln('USERNAME: '.$tenantUser->getUserIdentifier());
        $output->writeln('USER PASSWORD: '.$password);
        $output->writeln('API Credentials:');
        $output->writeln(Json::encode($tenantUser->getLogon($password), \JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }

    private function setBlamable(QuestionHelper $helper, InputInterface $input, OutputInterface $output): self
    {
        $question = $this->createDefaultQuestion('Your Email: ', 'villascape@gmail.com');
        $question->setValidator(function ($answer) {
            if (($systemUser = $this->getSystemUser(trim($answer))) !== null) {
                return $systemUser;
            }

            throw new RuntimeException('A system user does not exist for this email');
        });
        $systemUser = $helper->ask($input, $output, $question);
        $this->blameableListener->setUserValue($systemUser);
        return $this;
    }

    private function getExistingTenant(QuestionHelper $helper, InputInterface $input, OutputInterface $output): Tenant
    {
        $question = new Question('Tenant ID: ');
        $question->setValidator(function ($answer) {
            if (($tenant = $this->getTenant(trim($answer))) !== null) {
                return $tenant;
            }

            throw new RuntimeException('A tenant does not exist for this ID');
        });
        return $helper->ask($input, $output, $question);
    }

    private function getTenantData(array $tenant, QuestionHelper $helper, InputInterface $input, OutputInterface $output): array
    {
        $question = new Question('Tenant Name: ');
        $question->setValidator(function ($answer): string {
            if (!$answer) {
                throw new RuntimeException('A tenant name must be provided');
            }

            return $answer;
        });
        $tenant['name'] = $helper->ask($input, $output, $question);

        $question = $this->createDefaultQuestion('Tenant Timezone: ', $tenant['timezone']);
        $question->setValidator(function ($answer): string {
            if (!\in_array($answer, DateTimeZone::listIdentifiers(), true)) {
                throw new RuntimeException($answer.' is not a valid timezone');
            }
            return $answer;
        });

        $tenant['timezone'] = $helper->ask($input, $output, $question);
        $tenant['phoneNumber'] = $helper->ask($input, $output, $this->createDefaultQuestion('Organization Phone Number: ', $tenant['phoneNumber']));
        $tenant['address'] = $helper->ask($input, $output, $this->createDefaultQuestion('Organization Address: ', $tenant['address']));
        $tenant['city'] = $helper->ask($input, $output, $this->createDefaultQuestion('Organization City: ', $tenant['city']));
        $tenant['state'] = $helper->ask($input, $output, $this->createDefaultQuestion('Organization State: ', $tenant['state']));
        $tenant['zipcode'] = $helper->ask($input, $output, $this->createDefaultQuestion('Organization Zipcode: ', (string) $tenant['zipcode']));

        return $tenant;
    }

    private function displayTenantData(Tenant $tenant, OutputInterface $output): self
    {
        $output->writeln('Tenant Name: '.$tenant->getName());
        $output->writeln('Tenant Timezone: '.$tenant->getTimezone());
        $output->writeln('Tenant phone number: '.$this->formatPhoneNumber($tenant->getPhoneNumber()));
        $output->writeln('Tenant Address: '.$tenant->getLocation()->getFullAddress());
        return $this;
    }

    private function getTenant(string $id): ?Tenant
    {
        return $this->entityManager->getRepository(Tenant::class)->find($id);
    }

    private function createTenant(Tenant $tenant, array $data): Tenant
    {
        $data = $this->removeEmptyValues($data);
        $data['phoneNumber'] = $this->parsePhoneNumber($data['phoneNumber']);
        $mask = array_flip(['address', 'city', 'state', 'zipcode']);
        if($location = array_intersect_key($data, $mask)) {
            $location = $this->setValues($tenant->getLocation(), $location);
            $data = array_diff_key($data, $mask);
        }
        $this->setValues($tenant, $data);
        return $tenant;
    }
}
