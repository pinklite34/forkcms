<?php

namespace Backend\Modules\Tags\Domain\ModuleTag;

use Backend\Modules\Tags\Domain\Tag\Tag;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Backend\Modules\Tags\Domain\Tag\TagRepository")
 * @ORM\Table(name="TagsModuleTag", options={"collate"="utf8_general_ci", "charset"="utf8"})
 */
class ModuleTag
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string")
     */
    private $moduleName;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    private $moduleId;

    /**
     * @var Tag
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Backend\Modules\Tags\Domain\Tag\Tag", inversedBy="moduleTags")
     */
    private $tag;

    public function toArray(): array
    {
        return [
            'module' => $this->moduleName,
            'other_id' => $this->moduleId,
            'tag_id' => $this->tag->getId(),
        ];
    }
}
