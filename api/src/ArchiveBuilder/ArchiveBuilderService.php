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

namespace App\ArchiveBuilder;

use App\ArchiveBuilder\Dto\ArchivePhysicalMediaCollection;
use App\ArchiveBuilder\Dto\ArchiveSpec;
use App\ArchiveBuilder\Dto\HtmlAsset;
use App\ArchiveBuilder\Dto\HtmlPageCollection;
use App\ArchiveBuilder\Dto\PageService;
use App\ArchiveBuilder\Dto\HtmlPage;
use App\ArchiveBuilder\Dto\Content;
use App\Entity\Archive\Archive;
use App\Entity\Document\Document;
use App\Service\PhysicalMediaService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

final class ArchiveBuilderService
{
    public $filename;
    public $path;
    // Path in zip
    private const ZIP_DOCUMENT_PATH = 'documents';

    private string $htmlWrapper;

    public function __construct(
        private string $storagePath,
        private string $prototypePath,
        string $htmlWrapperPath,
        private array $pages,
        private DocumentNamer $documentNamer,
        private SpecTreeBuilder $specTreeBuilder,
        private PhysicalMediaService $physicalMediaService,
        private EntityManagerInterface $entityManager,
    ) {
        $this->htmlWrapper = file_get_contents($htmlWrapperPath);
    }

    public function create(Archive $archive): Archive
    {
        $tenant = $archive->getTenant();
        $project = $archive->getProject();

        $documents = $this->entityManager->getRepository(Document::class)->getProjectDocuments($project);
        $archivePhysicalMediaCollection = new ArchivePhysicalMediaCollection($this->documentNamer, self::ZIP_DOCUMENT_PATH, ...$documents);

        $archiveSpec = new ArchiveSpec(0, null, 'root', '');
        $archiveSpecTree = $this->specTreeBuilder->createSpecTree($project, $archiveSpec, $archivePhysicalMediaCollection);

        $htmlAsset = new HtmlAsset();
        $htmlPageCollection = new HtmlPageCollection(
            $htmlAsset,
        );

        $content = [
            'project.name' => $project->getName(),
            'project.id' => $project->getProjectId(),
            'date.start' => (($dateTime = $project->getStartAt()) !== null) ? $dateTime->format('Y-M-d') : null,
            'tenant.name' => $tenant->getName(),
        ];

        $pageService = new PageService($htmlPageCollection, $archivePhysicalMediaCollection, $archiveSpecTree);
        $template = $archive->getTemplate();

        foreach ($this->pages as $page) {
            $htmlPageCollection->addPage(new HtmlPage(
                new $page['page']($pageService),
                $page['name'],
                $page['filename'],
                $page['js'],
                $page['css'],
                new Content($content),
                $this->htmlWrapper,
                $template,
                $htmlAsset
            ));
        }
        $zipFilename = $this->createZip($htmlPageCollection, $archivePhysicalMediaCollection, $archive);
        $archive->setFilename($zipFilename);

        return $archive;
    }

    private function createZip(HtmlPageCollection $htmlPageCollection, ArchivePhysicalMediaCollection $archivePhysicalMediaCollection, Archive $archive): string
    {
        $path = $this->getPath($archive);
        if (!file_exists($path)) {
            mkdir($path);
        }
        $zipFilename = $this->getFilename($archive).'.zip';
        $zipFilepath = $path.'/'.$zipFilename;

        if (file_exists($zipFilepath)) {
            unlink($zipFilepath);
            // throw new Exception("$zipFilename already exists");
        }

        $zipArchive = new ZipArchive();
        if (true !== $zipArchive->open($zipFilepath, ZipArchive::CREATE)) {
            throw new Exception('ZIP not created');
        }

        // Add Standard Assets (JS, CSS, etc)
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->prototypePath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr((string) $filePath, \strlen($this->prototypePath) + 1);
                $zipArchive->addFile($filePath, $relativePath);
            }
        }

        // Add Dynamically created Assets (JS, CSS, etc)
        foreach ($htmlPageCollection->getJsScripts() as $filename => $script) {
            // $zip->addFromString(sprintf('%s/js/%s', self::ZIP_ASSET_PATH, $filename), $script);
            $zipArchive->addFromString($filename, $script);
        }
        foreach ($htmlPageCollection->getCssScripts() as $filename => $script) {
            // $zip->addFromString(sprintf('%s/css/%s',  self::ZIP_ASSET_PATH, $filename), $script);
            $zipArchive->addFromString($filename, $script);
        }

        // Add tenant's documents
        foreach ($archivePhysicalMediaCollection->getArchivePhysicalMedias() as $archivePhysicalMedia) {
            $zipArchive->addFile(
                $this->physicalMediaService->getFilePath($archivePhysicalMedia->getPhysicalMedia()),
                $archivePhysicalMedia->getZipFilepath()
            );
        }

        // Add webpages
        foreach ($htmlPageCollection->generatePages() as $filename => $generator) {
            $zipArchive->addFromString($filename, $generator);
        }

        $zipArchive->close();
        // chmod($zipFilepath, 0755);
        return $zipFilepath;
    }

    private function getPath(Archive $archive)
    {
        return sprintf('%s/tenant_%s', $this->storagePath, $archive->getTenant()->getId());
    }

    private function getFilename(Archive $archive)
    {
        return sprintf('%s_%s', str_replace(' ', '_', $archive->getProject()->getName()), $archive->getId());
    }
}
