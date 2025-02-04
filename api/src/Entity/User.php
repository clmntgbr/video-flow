<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\Entity\Traits\UuidTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/me',
            normalizationContext: ['skip_null_values' => false, 'groups' => ['user:get', 'media-pods:get', 'default']],
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;
    use UuidTrait;

    #[ORM\Column(length: 180)]
    #[Groups(['user:get'])]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $plainPassword = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups(['user:get'])]
    private ?string $givenName = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups(['user:get'])]
    private ?string $familyName = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups(['user:get'])]
    private ?string $avatarUrl = null;

    #[ORM\Column]
    #[Groups(['user:get'])]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(targetEntity: MediaPod::class, mappedBy: 'user', cascade: ['remove'])]
    #[Groups(['user:get'])]
    private Collection $mediaPods;

    public function __construct()
    {
        $this->initializeUuid();
        $this->mediaPods = new ArrayCollection();
    }

    #[Groups(['user:get'])]
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    #[Groups(['user:get'])]
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $password): static
    {
        $this->plainPassword = $password;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getGivenName(): ?string
    {
        return $this->givenName;
    }

    public function setGivenName(?string $givenName): static
    {
        $this->givenName = $givenName;

        return $this;
    }

    public function getFamilyName(): ?string
    {
        return $this->familyName;
    }

    public function getName(): ?string
    {
        return sprintf('%s %s', $this->givenName, $this->familyName);
    }

    public function setFamilyName(?string $familyName): static
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return Collection<int, MediaPod>
     */
    public function getMediaPods(): Collection
    {
        return $this->mediaPods;
    }

    public function addMediaPod(MediaPod $mediaPod): static
    {
        if (!$this->mediaPods->contains($mediaPod)) {
            $this->mediaPods->add($mediaPod);
            $mediaPod->setUser($this);
        }

        return $this;
    }

    public function removeMediaPod(MediaPod $mediaPod): static
    {
        if ($this->mediaPods->removeElement($mediaPod)) {
            // set the owning side to null (unless already changed)
            if ($mediaPod->getUser() === $this) {
                $mediaPod->setUser(null);
            }
        }

        return $this;
    }
}
