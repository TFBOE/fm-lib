<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/5/18
 * Time: 10:54 PM
 */

namespace Tfboe\FmLib\Tests\Entity;


use Doctrine\ORM\Mapping as ORM;
use Tfboe\FmLib\Entity\Helpers\BaseEntity;
use Tfboe\FmLib\Entity\RankingSystemListEntryInterface;

/**
 * Class RankingSystemList
 * @package Tfboe\FmLib\Entity
 * @ORM\Entity
 * @ORM\Table(name="rankingSystemListEntry")
 *
 *
 * Dynamic method hints for Elo ranking
 * @method int getPlayedGames()
 * @method RankingSystemListEntry setPlayedGames(int $playedGames)
 * @method int getRatedGames()
 * @method RankingSystemListEntry setRatedGames(int $ratedGames)
 * @method float getProvisoryRanking()
 * @method RankingSystemListEntry setProvisoryRanking(float $provisoryRanking)
 */
class RankingSystemListEntry extends BaseEntity implements RankingSystemListEntryInterface
{
  use \Tfboe\FmLib\Entity\Traits\RankingSystemListEntry;

  /**
   * RankingSystem constructor.
   * @param array $keys
   */
  public function __construct(array $keys)
  {
    $this->init($keys);
  }
}