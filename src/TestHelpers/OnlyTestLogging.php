<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/6/18
 * Time: 10:21 PM
 */

namespace Tfboe\FmLib\TestHelpers;


use Tfboe\FmLib\Helpers\Logging;

/**
 * Trait OnlyTestLogging
 * @package Tfboe\FmLib\TestHelpers
 */
trait OnlyTestLogging
{
//<editor-fold desc="Public Methods">
  /**
   * Sets the logging value testing to true
   * @before
   */
  public function setTestingTrue()
  {
    Logging::$testing = true;
  }
//</editor-fold desc="Public Methods">
}