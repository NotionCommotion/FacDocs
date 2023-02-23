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
//use Stof\DoctrineExtensionsBundle\EventListener\BlameListener;  //stof_doctrine_extensions.event_listener.blame
use Doctrine\ORM\EntityManagerInterface;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use App\Entity\Organization\SystemOrganization;
use App\Entity\Organization\OrganizationInterface;
use App\Entity\User\SystemUser;
use App\Entity\User\UserInterface;
use Symfony\Component\Uid\NilUlid;

abstract class AbstractAddUser extends Command
{
    public function __construct(protected EntityManagerInterface $entityManager, protected PhoneNumberUtil $phoneNumberUtil, protected ValidatorInterface $validator)
    {
        parent::__construct();
    }

    protected function getUserData(array $u, QuestionHelper $helper, InputInterface $input, OutputInterface $output): array
    {
        $question = $this->createDefaultQuestion('User\'s  First and Last Name: ', sprintf('%s %s', $u['firstName'], $u['lastName']));
        $question->setNormalizer(fn ($value): string => implode(' ', Strings::split(trim((string) $value), '#\s+#')))->setValidator(function ($answer): array {
            $answer = explode(' ', $answer);
            if (2 !== \count($answer)) {
                throw new RuntimeException('User\'s  First and Last Name name must be provided');
            }
            return $answer;
        });
        [$u['firstName'], $u['lastName']] = $helper->ask($input, $output, $question);
        $u['email'] = $helper->ask($input, $output, $this->createDefaultQuestion('User\'s  Email: ', $u['email']));
        $u['plainPassword'] = $helper->ask($input, $output, $this->createDefaultQuestion('User\'s  Password: ', $u['plainPassword']));
        $u['role'] = $helper->ask($input, $output, $this->createDefaultQuestion('User\'s  Role: ', $u['role']));
        $u['directPhoneNumber'] = $helper->ask($input, $output, $this->createDefaultQuestion('User\'s  Direct Phone Number: ', $u['directPhoneNumber']));
        $u['mobilePhoneNumber'] = $helper->ask($input, $output, $this->createDefaultQuestion('User\'s  Mobile Phone Number: ', $u['mobilePhoneNumber']));
        return $u;
    }

    protected function createUser(UserInterface $user, array $data): UserInterface
    {
        $data = $this->removeEmptyValues($data);
        $data['username'] = $data['username']??$data['email'];
        $data['roles'] = [$data['role']];
        unset($data['role']);
        $data['directPhoneNumber'] = $this->parsePhoneNumber($data['directPhoneNumber']);
        $data['mobilePhoneNumber'] = $this->parsePhoneNumber($data['mobilePhoneNumber']);
        $this->setValues($user, $data);
        return $user;
    }

    protected function displayUserData(UserInterface $u, OutputInterface $output): self
    {
        $output->writeln('User\'s first name: '.$u->getFirstName());
        $output->writeln('User\'s last name: '.$u->getLastName());
        $output->writeln('User\'s email and username: '.$u->getEmail());
        $output->writeln('User\'s password: '.$u->getPlainPassword());
        $output->writeln('User\'s roles: '.implode(', ', $u->getRoles()));
        $output->writeln('User\'s direct phone number: '.$this->formatPhoneNumber($u->getDirectPhoneNumber()));
        $output->writeln('User\'s mobile phone number: '.$this->formatPhoneNumber($u->getMobilePhoneNumber()));
        return $this;
    }

    protected function failsValidation(OrganizationInterface $organization, UserInterface $user, OutputInterface $output):?int
    {
        $organizationErrors = $this->validator->validate($organization);
        $userErrors = $this->validator->validate($user);

        if (\count($organizationErrors) > 0 || \count($userErrors) > 0) {
            foreach ($organizationErrors as $organizationError) {
                $output->writeln($organizationError->getMessage());
            }
            foreach ($userErrors as $userError) {
                $output->writeln($userError->getMessage());
            }
            return Command::FAILURE;
        }
        return null;
    }

    protected function parsePhoneNumber(?string $phoneNumber):?PhoneNumber
    {
        return $phoneNumber?$this->phoneNumberUtil->parse($phoneNumber, PhoneNumberUtil::UNKNOWN_REGION):null;
    }

    protected function formatPhoneNumber(?PhoneNumber $phoneNumber):?string
    {
        return $phoneNumber?$this->phoneNumberUtil->format($phoneNumber, PhoneNumberFormat::INTERNATIONAL):null;
    }

    protected function setValues($object, array $arr):self
    {
        foreach($arr as $name=>$value) {
            $object->{'set'.ucfirst($name)}($value);
        }
        return $this;
    }

    protected function removeEmptyValues(array $arr): array
    {
        return array_filter($arr, fn($value) => !is_null($value) && $value !== '');
    }

    protected function createDefaultQuestion(string $question, string $default): Question
    {
        return new Question(sprintf('%s: [%s]', $question, $default), $default);
    }

    protected function getSystemOrganization(): SystemOrganization
    {
        return $this->entityManager->getRepository(SystemOrganization::class)->find(new NilUlid);
    }

    protected function getSystemUser(string $username): ?SystemUser
    {
        return $this->entityManager->getRepository(SystemUser::class)->getUser($this->getSystemOrganization()->getId(), $username);
    }
}
