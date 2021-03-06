<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/4/18
 * Time: 4:10 PM
 */

namespace Tfboe\FmLib\Service;


use Tfboe\FmLib\Entity\TournamentInterface;

/**
 * Interface RankingSystemServiceInterface
 * @package Tfboe\FmLib\Service
 */
interface RankingSystemServiceInterface
{
//<editor-fold desc="Public Methods">
  /**
   * Adapts the open sync from property of all relevant ranking systems
   * @param TournamentInterface $tournament The tournament to analyze
   * @param mixed[][] $influences the earliest influences of the tournament before the change
   */
  public function adaptOpenSyncFromValues(TournamentInterface $tournament, array $influences): void;

  /**
   * Gets all ranking systems of a tournament and its earliest influences as time.
   * The result is used as input for the method applyRankingSystems.
   * @param TournamentInterface $tournament the tournament to analyze
   * @return mixed[][]
   */
  public function getRankingSystemsEarliestInfluences(TournamentInterface $tournament): array;

  /**
   * Recalculates all ranking systems which have an open sync from value.
   */
  public function recalculateRankingSystems(): void;
//</editor-fold desc="Public Methods">
}