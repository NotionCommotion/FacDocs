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
/**
 * If a Media record is not linked to one or more document records and is older than a given time duration, delete it from the database.
 * If a PhysicalMedia record is not linked to one or more media records and is older than a given time duration, delete it from the database and from the filesystem.
 */

namespace App\Command;

use App\Service\PhysicalMediaService;
use App\Repository\Document\MediaRepository;
use App\Repository\Document\PhysicalMediaRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'app:unused_media_deleter')]
final class OrphanMediaDeleter extends Command
{
    private const MEDIA_STALE_SECONDS_DURATION = 10;
    private const PHYSICAL_MEDIA_STALE_SECONDS_DURATION = 60;

    public function __construct(private PhysicalMediaService $physicalMediaService, private MediaRepository $mediaRepository, private PhysicalMediaRepository $physicalMediaRepository, ...$args)
    {
        parent::__construct(...$args);
    }

    protected function configure(): void
    {
        $this
        ->setDescription('Deletes orphaned Media from the database and orphaned PhysicalMedia from both the database and filesystem.')
        ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Dry run')
        ->setHelp('This command allows you to create a System User')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('dry-run')) {
            $io->note('Dry mode enabled');

            $mediaCount = $this->mediaRepository->countOrphanMedia(self::MEDIA_STALE_SECONDS_DURATION);
            $physicalMediaCount = $this->physicalMediaRepository->countOrphanPhysicalMedia(self::PHYSICAL_MEDIA_STALE_SECONDS_DURATION);
        } else {
            $mediaCount = $this->mediaRepository->deleteOrphanMedia(self::MEDIA_STALE_SECONDS_DURATION);
            foreach($this->physicalMediaRepository->getOrphanPhysicalMedia(self::PHYSICAL_MEDIA_STALE_SECONDS_DURATION) as $physicalMedia) {
                $physicalMediaService->delete($physicalMedia);
            }
            $physicalMediaCount = $this->physicalMediaRepository->deleteOrphans(self::PHYSICAL_MEDIA_STALE_SECONDS_DURATION);
        }

        $io->success(sprintf('Deleted "%d" orphaned media records and "%d" orphaned physical media records and files.', $mediaCount, $physicalMediaCount));

        return 0;
    }
}
