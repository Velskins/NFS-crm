<?php

namespace App\Entity;

use App\Repository\QuoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
class Quote
{
    public const STATUS_DRAFT = 'brouillon';
    public const STATUS_SENT = 'envoye';
    public const STATUS_ACCEPTED = 'accepte';
    public const STATUS_REFUSED = 'refuse';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $quoteNumber = null;

    #[ORM\Column(length: 50)]
    private ?string $status = self::STATUS_DRAFT;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $validUntil = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\ManyToOne(inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'quotes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, QuoteLine>
     */
    #[ORM\OneToMany(targetEntity: QuoteLine::class, mappedBy: 'quote', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lines;

    #[ORM\OneToOne(targetEntity: Project::class, mappedBy: 'quote')]
    private ?Project $project = null;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->validUntil = new \DateTimeImmutable('+30 days');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuoteNumber(): ?string
    {
        return $this->quoteNumber;
    }

    public function setQuoteNumber(string $quoteNumber): static
    {
        $this->quoteNumber = $quoteNumber;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function setValidUntil(\DateTimeImmutable $validUntil): static
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Collection<int, QuoteLine>
     */
    public function getLines(): Collection
    {
        return $this->lines;
    }

    public function addLine(QuoteLine $line): static
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setQuote($this);
        }
        return $this;
    }

    public function removeLine(QuoteLine $line): static
    {
        if ($this->lines->removeElement($line)) {
            if ($line->getQuote() === $this) {
                $line->setQuote(null);
            }
        }
        return $this;
    }

    /**
     * Calcule le montant total du devis
     */
    public function getTotalAmount(): float
    {
        $total = 0;
        foreach ($this->lines as $line) {
            $total += $line->getTotalPrice();
        }
        return $total;
    }

    /**
     * Vérifie si le devis est expiré
     */
    public function isExpired(): bool
    {
        return $this->validUntil < new \DateTimeImmutable();
    }

    /**
     * Retourne le libellé du statut
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Brouillon',
            self::STATUS_SENT => 'Envoyé',
            self::STATUS_ACCEPTED => 'Accepté',
            self::STATUS_REFUSED => 'Refusé',
            default => $this->status,
        };
    }

    /**
     * Liste des statuts possibles
     */
    public static function getStatuses(): array
    {
        return [
            'Brouillon' => self::STATUS_DRAFT,
            'Envoyé' => self::STATUS_SENT,
            'Accepté' => self::STATUS_ACCEPTED,
            'Refusé' => self::STATUS_REFUSED,
        ];
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        if ($project !== null && $project->getQuote() !== $this) {
            $project->setQuote($this);
        }
        $this->project = $project;
        return $this;
    }

    public function isConverted(): bool
    {
        return $this->project !== null;
    }
}
