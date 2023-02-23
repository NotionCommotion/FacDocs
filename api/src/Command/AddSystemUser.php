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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Uid\NilUlid;
use App\Entity\Organization\SystemOrganization;
use App\Entity\User\SystemUser;
//use Gedmo\Blameable\BlameableListener;

// bin/console app:add-system-user
#[AsCommand(name: 'app:add-system-user')]
final class AddSystemUser extends AbstractAddUser
{
    private const DEFAULT_USER =[
        'firstName' => 'Michael',
        'lastName' => 'Reed',
        'email' => 'villascape@gmail.com',
        'plainPassword' => 'changeMe',
        'role' => 'ROLE_SYSTEM_ADMIN',
        'directPhoneNumber' => '+14259499433',
        'mobilePhoneNumber' => '+14257853633',
    ];
    protected function configure(): void
    {
        $this
        ->setDescription('System User Creater.  --help')
        ->setHelp('This command allows you to create a System User');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'System User Creater',
            '============',
            '',
        ]);

        $helper = $this->getHelper('question');

        $systemOrganization = $this->getSystemOrganization();
        $systemUser = $this->createUser((new SystemUser())->setOrganization($systemOrganization), $this->getUserData(self::DEFAULT_USER, $helper, $input, $output));

        $output->writeln('');
        $output->writeln('Following values will be used.');
        $this->displayUserData($systemUser, $output);

        $question = new ConfirmationQuestion('Continue with this action?', true);
        if (!$helper->ask($input, $output, $question)) {
            return Command::SUCCESS;
        }

        $output->writeln('');

        if($error = $this->failsValidation($systemOrganization, $systemUser, $output)) {
            return $error;
        }

        $password = $systemUser->getPlainPassword();

        $this->entityManager->persist($systemUser);
        try {
            $this->entityManager->flush();
        } catch (Exception $exception) {
            $output->writeln($exception->getMessage());
            return Command::FAILURE;
        }

        $output->writeln('System User successfully generated!');
        $output->writeln('USER ID: '.$systemUser->getId());
        $output->writeln('USERNAME: '.$systemUser->getUserIdentifier());
        $output->writeln('USER PASSWORD: '.$password);
        $output->writeln('API Credentials:');
        $output->writeln(Json::encode($systemUser->getLogon($password), \JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }
}
