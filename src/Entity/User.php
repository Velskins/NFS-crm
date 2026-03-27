<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invitationToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $invitationTokenExpiresAt = null;

    #[ORM\OneToOne(targetEntity: Client::class, mappedBy: 'userAccount')]
    private ?Client $clientProfile = null;

    // ── Champs facturation (ROLE_ADMIN) ──
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyAddress = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $companyPostalCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $companyCity = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $siret = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $tvaNumber = null;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $phone = null;

    // ── Préférences de notifications ──
    #[ORM\Column(type: 'boolean')]
    private bool $notifEcheance = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notifNewProject = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notifDocumentUploaded = true;

    #[ORM\Column(type: 'boolean')]
    private bool $notifPaymentReceived = true;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'user')]
    private Collection $clients;

    /**
     * @var Collection<int, Project>
     */
    #[ORM\OneToMany(targetEntity: Project::class, mappedBy: 'user')]
    private Collection $projects;

    /**
     * @var Collection<int, Messagrie>
     */
    #[ORM\OneToMany(targetEntity: Messagrie::class, mappedBy: 'user')]
    private Collection $messagries;

    /**
     * @var Collection<int, Invoice>
     */
    #[ORM\OneToMany(targetEntity: Invoice::class, mappedBy: 'user')]
    private Collection $invoices;

    /**
     * @var Collection<int, Appointment>
     */
    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'user')]
    private Collection $appointments;

    /**
     * @var Collection<int, Invoice>
     */

    public function __construct()
    {
        $this->clients = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->messagries = new ArrayCollection();
        $this->invoices = new ArrayCollection();
        $this->appointments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
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
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
            $client->setUser($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getUser() === $this) {
                $client->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Project>
     */
    public function getProjects(): Collection
    {
        return $this->projects;
    }

    public function addProject(Project $project): static
    {
        if (!$this->projects->contains($project)) {
            $this->projects->add($project);
            $project->setUser($this);
        }

        return $this;
    }

    public function removeProject(Project $project): static
    {
        if ($this->projects->removeElement($project)) {
            // set the owning side to null (unless already changed)
            if ($project->getUser() === $this) {
                $project->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Messagrie>
     */
    public function getMessagries(): Collection
    {
        return $this->messagries;
    }

    public function addMessagry(Messagrie $messagry): static
    {
        if (!$this->messagries->contains($messagry)) {
            $this->messagries->add($messagry);
            $messagry->setUser($this);
        }

        return $this;
    }

    public function removeMessagry(Messagrie $messagry): static
    {
        if ($this->messagries->removeElement($messagry)) {
            // set the owning side to null (unless already changed)
            if ($messagry->getUser() === $this) {
                $messagry->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Invoice>
     */

    /**
     * @return Collection<int, Invoice>
     */
    public function getInvoices(): Collection
    {
        return $this->invoices;
    }

    public function addInvoice(Invoice $invoice): static
    {
        if (!$this->invoices->contains($invoice)) {
            $this->invoices->add($invoice);
            $invoice->setUser($this);
        }

        return $this;
    }

    public function removeInvoice(Invoice $invoice): static
    {
        if ($this->invoices->removeElement($invoice)) {
            // set the owning side to null (unless already changed)
            if ($invoice->getUser() === $this) {
                $invoice->setUser(null);
            }
        }

        return $this;
    }

    public function getInvitationToken(): ?string
    {
        return $this->invitationToken;
    }

    public function setInvitationToken(?string $invitationToken): static
    {
        $this->invitationToken = $invitationToken;
        return $this;
    }

    public function getInvitationTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->invitationTokenExpiresAt;
    }

    public function setInvitationTokenExpiresAt(?\DateTimeImmutable $invitationTokenExpiresAt): static
    {
        $this->invitationTokenExpiresAt = $invitationTokenExpiresAt;
        return $this;
    }

    public function isInvitationTokenValid(): bool
    {
        return $this->invitationToken !== null
            && $this->invitationTokenExpiresAt !== null
            && $this->invitationTokenExpiresAt > new \DateTimeImmutable();
    }

    public function getClientProfile(): ?Client
    {
        return $this->clientProfile;
    }

    public function setClientProfile(?Client $clientProfile): static
    {
        if ($clientProfile !== null && $clientProfile->getUserAccount() !== $this) {
            $clientProfile->setUserAccount($this);
        }
        $this->clientProfile = $clientProfile;
        return $this;
    }

    // ── Facturation ──

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getCompanyAddress(): ?string
    {
        return $this->companyAddress;
    }

    public function setCompanyAddress(?string $companyAddress): static
    {
        $this->companyAddress = $companyAddress;
        return $this;
    }

    public function getCompanyPostalCode(): ?string
    {
        return $this->companyPostalCode;
    }

    public function setCompanyPostalCode(?string $companyPostalCode): static
    {
        $this->companyPostalCode = $companyPostalCode;
        return $this;
    }

    public function getCompanyCity(): ?string
    {
        return $this->companyCity;
    }

    public function setCompanyCity(?string $companyCity): static
    {
        $this->companyCity = $companyCity;
        return $this;
    }

    public function getSiret(): ?string
    {
        return $this->siret;
    }

    public function setSiret(?string $siret): static
    {
        $this->siret = $siret;
        return $this;
    }

    public function getTvaNumber(): ?string
    {
        return $this->tvaNumber;
    }

    public function setTvaNumber(?string $tvaNumber): static
    {
        $this->tvaNumber = $tvaNumber;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    // ── Notifications ──

    public function isNotifEcheance(): bool
    {
        return $this->notifEcheance;
    }

    public function setNotifEcheance(bool $notifEcheance): static
    {
        $this->notifEcheance = $notifEcheance;
        return $this;
    }

    public function isNotifNewProject(): bool
    {
        return $this->notifNewProject;
    }

    public function setNotifNewProject(bool $notifNewProject): static
    {
        $this->notifNewProject = $notifNewProject;
        return $this;
    }

    public function isNotifDocumentUploaded(): bool
    {
        return $this->notifDocumentUploaded;
    }

    public function setNotifDocumentUploaded(bool $notifDocumentUploaded): static
    {
        $this->notifDocumentUploaded = $notifDocumentUploaded;
        return $this;
    }

    public function isNotifPaymentReceived(): bool
    {
        return $this->notifPaymentReceived;
    }

    public function setNotifPaymentReceived(bool $notifPaymentReceived): static
    {
        $this->notifPaymentReceived = $notifPaymentReceived;
        return $this;
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setUser($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            // set the owning side to null (unless already changed)
            if ($appointment->getUser() === $this) {
                $appointment->setUser(null);
            }
        }

        return $this;
    }

}
