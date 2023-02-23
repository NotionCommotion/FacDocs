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

namespace App\Entity\Trait\UserAction;

use App\Entity\User\UserInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;
use ApiPlatform\Metadata\ApiProperty;

trait UserCreateActionTrait
{
    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'create')]
    #[Groups(['user_action:read'])]
    protected ?DateTime $createAt = null;

    // Don't think it is possible with Doctrine to install a record in itself with a non-null constraint.  See //https://stackoverflow.com/questions/69742779/how-to-persist-an-object-with-a-reference-to-itself-using-doctrine
    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Gedmo\Blameable(on: 'create')]
    #[Groups(['user_action:read'])]
    // Needed since this can be both in the parent and child.  How to fix?
    // #[MaxDepth(1)]    //Doesn't work!
    #[ApiProperty(readableLink: false, writableLink: false)]
    protected ?UserInterface $createBy = null;

    public function getCreateAt(): ?DateTime
    {
        return $this->createAt;
    }

    public function getCreateBy(): ?UserInterface
    {
        return $this->createBy;
    }

    // Shouldn't need this?  Maybe only by HelpDesk?
    public function setCreateAt(DateTime $date): self
    {
        $this->createAt = $date;
        
        return $this;
    }
    public function setCreateBy(UserInterface $user): self
    {
        $this->createBy = $user;
        
        return $this;
    }
}
