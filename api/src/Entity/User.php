<?php

namespace App\Entity;

use App\Entity\Comment;
use App\Entity\Subscription;
use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use App\State\UserPasswordHasher;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    operations: [
        new Post(processor: UserPasswordHasher::class),
        new Get(),
        new GetCollection(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:create', 'user:update']],
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]

#[UniqueEntity('email')]
#[ApiFilter(SearchFilter::class, properties: ['email' => 'exact', 'roles' => 'exact'])]
#[UniqueEntity('email', 'Cet email est déjà utilisé')]

class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[Groups(['user:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message:"L'email n'est pas valide.")]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(message: "Le mot de passe est obligatoire")]
    #[Assert\Length(
        min: 8,
        max: 10,
        minMessage: "Le mot de passe doit comporter au moins 8 caractères.",
        maxMessage: "Le mot de passe doit comporter au maximum 10 caractères."
    )]
    #[Groups(['user:create', 'user:update'])]
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'json')]
    #[Groups(['user:read', 'user:create', 'user:update'])]
    private array $roles = ['ROLE_USER'];

    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:create', 'user:update', 'read:item:ticket'])]
    private ?string $firstname = null;

    #[Assert\NotBlank(message: "Le nom de famille est obligatoire.")]
    #[ORM\Column(length: 255)]
    #[Groups(['user:read', 'user:create', 'user:update', 'read:item:ticket'])]
    private ?string $lastname = null;

    #[ORM\OneToMany(mappedBy: 'user_id', targetEntity: Comment::class)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'user_id', targetEntity: Ticket::class)]
    private Collection $tickets;
    
    #[ORM\OneToMany(mappedBy: 'user_sub', targetEntity: Subscription::class)]
    private Collection $subscriptions;

    #[ORM\OneToMany(mappedBy: 'user_id', targetEntity: Moderation::class)]
    private Collection $moderations;


    #[ORM\OneToMany(mappedBy: 'user_admin', targetEntity: Review::class)]
    #[Groups(['user:read', 'user:create', 'user:update', 'read:item:ticket'])]
    private Collection $reviews;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->tickets = new ArrayCollection();
        $this->moderations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $painPassword): self
    {
        $this->plainPassword = $painPassword;

        return $this;
    }


    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setUserId($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getUserId() === $this) {
                $comment->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;

        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): self
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setUserId($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): self
    {
        if ($this->tickets->removeElement($ticket)) {
            // set the owning side to null (unless already changed)
            if ($ticket->getUserId() === $this) {
                $ticket->setUserId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    /**
     * @return Collection<int, Moderation>
     */
    public function getModerations(): Collection
    {
        return $this->moderations;
    }

    public function addModeration(Moderation $moderation): self
    {
        if (!$this->moderations->contains($moderation)) {
            $this->moderations->add($moderation);
            $moderation->setUserId($this);
        }

        return $this;
    }

    public function removeModeration(Moderation $moderation): self
    {
        if ($this->moderations->removeElement($moderation)) {
            // set the owning side to null (unless already changed)
            if ($moderation->getUserId() === $this) {
                $moderation->setUserId(null);
            }
        }

        return $this;
    }

    /* A visual identifier that represents this user.
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
    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }


    // /**
    //  * @return Collection<int, Review>
    //  */
    // public function getReviews(): Collection
    // {
    //     return $this->reviews;
    // }

    // public function addReview(Review $review): self
    // {
    //     if (!$this->reviews->contains($review)) {
    //         $this->reviews->add($review);
    //         $review->setUserAdminCheck($this);
    //     }

    //     return $this;
    // }

    // public function removeReview(Review $review): self
    // {
    //     if ($this->reviews->removeElement($review)) {
    //         // set the owning side to null (unless already changed)
    //         if ($review->getUserAdminCheck() === $this) {
    //             $review->setUserAdminCheck(null);
    //         }
    //     }

    //     return $this;
    // }

    // /**
    //  * @return Collection<int, Subscription>
    //  */
    // public function getSubscriptions(): Collection
    // {
    //     return $this->subscriptions;
    // }

    // public function addSubscription(Subscription $subscription): self
    // {
    //     if (!$this->subscriptions->contains($subscription)) {
    //         $this->subscriptions->add($subscription);
    //         $subscription->setUserSub($this);
    //     }

    //     return $this;
    // }

    // public function removeSubscription(Subscription $subscription): self
    // {
    //     if ($this->subscriptions->removeElement($subscription)) {
    //         // set the owning side to null (unless already changed)
    //         if ($subscription->getUserSub() === $this) {
    //             $subscription->setUserSub(null);
    //         }
    //     }

    //     return $this;
    // }
}