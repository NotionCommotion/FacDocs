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

namespace App\Entity\Project;

use Exception;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Entity\MultiTenenacy\BelongsToTenantInterface;
use App\Entity\MultiTenenacy\BelongsToTenantTrait;
use App\Entity\User\JobTitle;
use App\Entity\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource]
#[ORM\Entity]
class ProjectTeamMember implements BelongsToTenantInterface
{
    use BelongsToTenantTrait;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: UserInterface::class, inversedBy: 'projectTeamMembers')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(openapiContext: ['example' => 'tenant_users/00000000000000000000000000'])]
    #[Groups(['project_directory_member:read', 'project_directory_member:write'])]
    private ?UserInterface $user = null;
    
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'projectTeamMembers')]
    #[ORM\JoinColumn(nullable: false)]
    #[ApiProperty(openapiContext: ['example' => 'projects/00000000000000000000000000'])]
    #[Groups(['project_directory_member:read', 'project_directory_member:write'])]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: JobTitle::class)]
    #[ApiProperty(openapiContext: ['example' => 'job_titles/00000000000000000000000000'])]
    private $jobTitle;

    #[Groups(['project:read', 'user:read', 'project:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'integer')]
    private ?int $list_order = null;

    public function debug(int $follow=0, bool $verbose = false, array $hide=[]):array
    {
        return ['id'=>$this->id, 'id-rfc4122'=>$this->id?$this->id->toRfc4122():'NULL', 'user'=>$this->user?$this->user->debug($follow, $verbose, $hide):null, 'project'=>$this->project?$this->project->debug($follow, $verbose, $hide):null, 'class'=>get_class($this)];
    }

    public function getUser(): UserInterface
    {
        return $this->user;
    }

    public function setUser(UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getJobTitle(): ?JobTitle
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?JobTitle $jobTitle): self
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getListOrder(): ?int
    {
        return $this->list_order;
    }

    public function setListOrder(int $list_order): self
    {
        $this->list_order = $list_order;

        return $this;
    }
}
