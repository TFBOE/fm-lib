<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 10/20/17
 * Time: 12:30 PM
 */

namespace Tfboe\FmLib\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tfboe\FmLib\Entity\Helpers\BaseEntity;
use Tfboe\FmLib\Entity\TeamInterface;

/**
 * Class Team
 * @package Tfboe\FmLib\Entity
 * @ORM\Entity
 * @ORM\Table(name="teams")
 */
class Team extends BaseEntity implements TeamInterface
{
  use \Tfboe\FmLib\Entity\Traits\Team;

  /**
   * RankingSystem constructor.
   */
  public function __construct()
  {
    $this->init();
  }
}