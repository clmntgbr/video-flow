<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Entity\Traits\UuidTrait;
use App\Repository\VideoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: VideoRepository::class)]
#[ApiResource(
    operations: [
    ],
)]
class Video
{
    use TimestampableEntity;
    use UuidTrait;

    #[ORM\Column(type: Types::STRING)]
    #[Groups(['media-pods:get'])]
    private ?string $originalName = null;

    #[ORM\Column(type: Types::STRING)]
    #[Groups(['media-pods:get'])]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING)]
    #[Groups(['media-pods:get'])]
    private ?string $mimeType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Groups(['media-pods:get'])]
    private ?int $size = null;

    public function __construct()
    {
        $this->initializeUuid();
    }

    #[Groups(['media-pods:get'])]
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[Groups(['media-pods:get'])]
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): static
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }
}
