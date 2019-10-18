<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/24/18
 * Time: 2:55 PM
 */

namespace Tfboe\FmLib\Providers;

/**
 * Class FmLibServiceProvider
 * @package Tfboe\FmLib\Providers
 */
abstract class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
//<editor-fold desc="Fields">
  protected $singletons = [];
//</editor-fold desc="Fields">

//<editor-fold desc="Public Methods">
  public function register()
  {
    parent::register();
    foreach ($this->singletons as $key => $singleton) {
      if (is_int($key)) {
        if (substr($singleton, strlen($singleton) - strlen("Interface")) === "Interface") {
          $this->app->singleton($singleton, substr($singleton, 0, strlen($singleton) - strlen("Interface")));
        } else {
          $this->app->singleton($singleton);
        }
      } else {
        $this->app->singleton($key, $singleton);
      }
    }
  }
//</editor-fold desc="Public Methods">
}