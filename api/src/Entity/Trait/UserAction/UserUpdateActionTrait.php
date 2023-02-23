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

trait UserUpdateActionTrait
{
    // Gedmo\Timestampable implemented in concrete class since needs a list of fields to update.
    
    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    #[Groups(['user_action:read'])]
    protected ?DateTime $updateAt = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Gedmo\Blameable(on: 'update')]
    #[Groups(['user_action:read'])]
    // Needed since this can be both in the parent and child.  How to fix?
    // #[MaxDepth(1)]    //Doesn't work!
    #[ApiProperty(readableLink: false, writableLink: false)]
    protected ?UserInterface $updateBy = null;

    public function getUpdateAt(): ?DateTime
    {
        return $this->updateAt;
    }

    public function getUpdateBy(): ?UserInterface
    {
        return $this->updateBy;
    }

    // Shouldn't need this?  Maybe only by HelpDesk?
    public function setUpdateAt(DateTime $date): self
    {
        $this->createAt = $date;
        
        return $this;
    }
    public function setUpdateBy(UserInterface $user): self
    {
        $this->createBy = $user;
        
        return $this;
    }
}
