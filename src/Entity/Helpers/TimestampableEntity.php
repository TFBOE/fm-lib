<?php
declare(strict_types=1);

namespace Tfboe\FmLib\Entity\Helpers;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trait TimestampableEntity
 * @package Tfboe\FmLib\Entity\Helpers
 */
trait TimestampableEntity
{
//<editor-fold desc="Fields">
  /**
   * @var \DateTime
   * @\Gedmo\Mapping\Annotation\Timestampable(on="create")
   * @ORM\Column(type="datetime")
   */
  private $createdAt;

  /**
   * @var \DateTime
   * @\Gedmo\Mapping\Annotation\Timestampable(on="update")
   * @ORM\Column(type="datetime")
   */
  private $updatedAt;

//</editor-fold desc="Fields">

//<editor-fold desc="Public Methods">
  /**
   * @return \DateTime
   */
  public function getCreatedAt(): \DateTime
  {
    return $this->createdAt;
  }

  /**
   * @return \DateTime
   */
  public function getUpdatedAt(): \DateTime
  {
    return $this->updatedAt;
  }

  /**
   * @param \DateTime $createdAt
   * @return $this|TimestampableEntity
   */
  public function setCreatedAt(\DateTime $createdAt)
  {
    $this->createdAt = $createdAt;
    return $this;
  }

  /**
   * @param \DateTime $updatedAt
   * @return $this|TimestampableEntity
   */
  public function setUpdatedAt(\DateTime $updatedAt)
  {
    $this->updatedAt = $updatedAt;
    return $this;
  }
//</editor-fold desc="Public Methods">
}
