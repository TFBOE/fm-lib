<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/7/18
 * Time: 9:01 PM
 */

namespace Tfboe\FmLib\Service\RankingSystem;


use Tfboe\FmLib\Entity\Helpers\TournamentHierarchyInterface;

/**
 * Interface TimeService
 * @package Tfboe\FmLib\Service\RankingSystemService\TimeService
 */
interface TimeServiceInterface
{
//<editor-fold desc="Public Methods">
  /**
   * If the service has a memory clears all remembered times. This needs to be called when times of some entities
   * changed or simply at the beginning of a new calculation.
   */
  public function clearTimes();

  /**
   * Gets the relevant time for the given entity for ordering purposes
   * @param TournamentHierarchyInterface $entity
   * @return \DateTime
   */
  public function getTime(TournamentHierarchyInterface $entity);
//</editor-fold desc="Public Methods">
}