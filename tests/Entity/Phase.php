<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/28/18
 * Time: 11:38 PM
 */

namespace Tfboe\FmLib\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity;
use Tfboe\FmLib\Entity\PhaseInterface;

/**
 * Class Phase
 * @package Tfboe\FmLib\Tests\Entity
 * @ORM\Entity
 * @ORM\Table(name="phases")
 */
class Phase extends TournamentHierarchyEntity implements PhaseInterface
{
  use \Tfboe\FmLib\Entity\Traits\Phase;

//<editor-fold desc="Constructor">

  /**
   * Phase constructor.
   */
  public function __construct()
  {
    parent::__construct();
    $this->init();
  }
//</editor-fold desc="Constructor">
}