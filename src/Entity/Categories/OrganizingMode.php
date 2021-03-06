<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 9/18/17
 * Time: 10:22 PM
 */

namespace Tfboe\FmLib\Entity\Categories;

use Tfboe\FmLib\Helpers\BasicEnum;


/**
 * Class OrganizingMode
 * @package Tfboe\FmLib\Entity\Categories
 */
abstract class OrganizingMode extends BasicEnum
{
//<editor-fold desc="Fields">
  const ELIMINATION = 0;
  const QUALIFICATION = 1;
//</editor-fold desc="Fields">
}