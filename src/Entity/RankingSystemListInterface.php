<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/30/18
 * Time: 5:52 PM
 */

namespace Tfboe\FmLib\Entity;

use Doctrine\Common\Collections\Collection;
use Tfboe\FmLib\Entity\Helpers\BaseEntityInterface;
use Tfboe\FmLib\Entity\Helpers\UUIDEntityInterface;

/**
 * Interface RankingSystemListInterface
 * @package Tfboe\FmLib\Entity
 */
interface RankingSystemListInterface extends BaseEntityInterface, UUIDEntityInterface
{
//<editor-fold desc="Public Methods">
  /**
   * @return RankingSystemListEntryInterface[]|Collection
   */
  public function getEntries(): Collection;

  /**
   * @return \DateTime
   */
  public function getLastEntryTime(): \DateTime;

  /**
   * @return RankingSystemInterface
   */
  public function getRankingSystem(): RankingSystemInterface;

  /**
   * @return bool
   */
  public function isCurrent(): bool;

  /**
   * @param bool $current
   */
  public function setCurrent(bool $current);

  /**
   * @param \DateTime $lastEntryTime
   */
  public function setLastEntryTime(\DateTime $lastEntryTime);

  /**
   * @param RankingSystemInterface $rankingSystem
   */
  public function setRankingSystem(RankingSystemInterface $rankingSystem);
//</editor-fold desc="Public Methods">
}