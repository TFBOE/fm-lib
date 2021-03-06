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
use Tfboe\FmLib\Entity\Helpers\BaseEntity;
use Tfboe\FmLib\Entity\LastRecalculationInterface;

/**
 * Class Game
 * @package Tfboe\FmLib\Tests\Entity
 * @ORM\Entity
 * @ORM\Table(name="lastRecalculation")
 */
class LastRecalculation extends BaseEntity implements LastRecalculationInterface
{
  use \Tfboe\FmLib\Entity\Traits\LastRecalculation;
}