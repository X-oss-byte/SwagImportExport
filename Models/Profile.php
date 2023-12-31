<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Models;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Shopware\Components\Model\ModelEntity;

/**
 * @ORM\Table(name="s_import_export_profile")
 * @ORM\Entity(repositoryClass="ProfileRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Profile extends ModelEntity
{
    /**
     * Primary Key - autoincrement value
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected int $id;

    /**
     * @var Collection<int, Expression>
     *
     * @ORM\OneToMany(targetEntity="SwagImportExport\Models\Expression", mappedBy="profile")
     * @ORM\JoinColumn(nullable=true)
     */
    private Collection $expressions;

    /**
     * @var Collection<int, Session>
     *
     * @ORM\OneToMany(targetEntity="SwagImportExport\Models\Session", mappedBy="profile")
     */
    private Collection $sessions;

    /**
     * @ORM\Column(name="type", type="string", length=200)
     */
    private string $type;

    /**
     * @ORM\Column(name="base_profile", type="integer", nullable=true)
     */
    private ?int $baseProfile;

    /**
     * @ORM\Column(name="name", type="string", length=200, unique=true)
     */
    private string $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    private ?string $description;

    /**
     * @ORM\Column(name="tree", type="text")
     */
    private string $tree;

    /**
     * @var bool hidden
     *
     * @ORM\Column(name="hidden", type="boolean")
     */
    private bool $hidden = false;

    /**
     * @ORM\Column(name="is_default", type="boolean")
     */
    private bool $default = false;

    public function __construct()
    {
        $this->expressions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getBaseProfile(): ?int
    {
        return $this->baseProfile;
    }

    public function getTree(): string
    {
        return $this->tree;
    }

    /**
     * @return Collection<int, Expression>
     */
    public function getExpressions(): Collection
    {
        return $this->expressions;
    }

    /**
     * @return iterable<Session>
     */
    public function getSessions(): iterable
    {
        return $this->sessions;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setBaseProfile(?int $baseProfile): void
    {
        $this->baseProfile = $baseProfile;
    }

    public function setTree(string $tree): void
    {
        $this->tree = $tree;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getHidden(): bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): void
    {
        $this->hidden = $hidden;
    }

    public function addExpression(Expression $expression): Profile
    {
        $this->expressions[] = $expression;
        $expression->setProfile($this);

        return $this;
    }

    /**
     * Adds a session to the profile.
     */
    public function addSession(Session $session): Profile
    {
        $this->sessions[] = $session;
        $session->setProfile($this);

        return $this;
    }

    public function getDefault(): bool
    {
        return $this->default;
    }

    public function setDefault(bool $default): void
    {
        $this->default = $default;
    }
}
