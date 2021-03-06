<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/2/18
 * Time: 8:47 PM
 */

namespace Tfboe\FmLib\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tfboe\FmLib\Entity\Helpers\BaseEntity;
use Tfboe\FmLib\Entity\RankingSystemInterface;

/**
 * Class RankingSystemService
 * @package Tfboe\FmLib\Entity
 * @ORM\Entity
 * @ORM\Table(name="rankingSystems")
 */
class RankingSystem extends BaseEntity implements RankingSystemInterface
{
  use \Tfboe\FmLib\Entity\Traits\RankingSystem;

  /**
   * RankingSystem constructor.
   * @param array $keys
   */
  public function __construct(array $keys)
  {
    $this->init($keys);
  }
}