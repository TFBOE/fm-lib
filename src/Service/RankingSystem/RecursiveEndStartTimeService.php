<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/7/18
 * Time: 9:04 PM
 */

namespace Tfboe\FmLib\Service\RankingSystem;


use Tfboe\FmLib\Entity\Helpers\TournamentHierarchyInterface;
use Tfboe\FmLib\Entity\TournamentInterface;

/**
 * Class RecursiveEndStartTimeService
 * @package Tfboe\FmLib\Service\RankingSystemListService
 */
class RecursiveEndStartTimeService implements TimeServiceInterface
{
//<editor-fold desc="Fields">
  /** @var \DateTime[] */
  private $times = [];
//</editor-fold desc="Fields">

//<editor-fold desc="Public Methods">
  /**
   * @inheritDoc
   */
  public function clearTimes()
  {
    $this->times = [];
  }

  /**
   * @inheritDoc
   */
  public function getTime(TournamentHierarchyInterface $entity)
  {
    $entityId = $entity->getId();
    if (!array_key_exists($entityId, $this->times)) {
      $this->times[$entityId] = $entity->getEndTime();
      if ($this->times[$entityId] == null) {
        $this->times[$entityId] = $entity->getStartTime();
        if ($this->times[$entityId] == null) {
          if ($entity->getParent() !== null) {
            $this->times[$entityId] = $this->getTime($entity->getParent());
          } else {
            //entity must be a tournament
            /** @var TournamentInterface $entity */
            $this->times[$entityId] = $entity->getUpdatedAt();
          }
        }
      }
    }
    return $this->times[$entityId];
  }
//</editor-fold desc="Public Methods">
}