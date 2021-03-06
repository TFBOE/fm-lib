<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 9/15/17
 * Time: 11:18 PM
 */

namespace Tfboe\FmLib\Exceptions;


use Throwable;

/**
 * Class AuthenticationException
 * @package Tfboe\FmLib\Exceptions
 */
class AuthenticationException extends AbstractException
{
//<editor-fold desc="Constructor">
  /**
   * AuthenticationException constructor.
   * @param string $message
   * @param Throwable|null $previous
   */
  public function __construct($message = "", Throwable $previous = null)
  {
    parent::__construct($message, 401, $previous);
  }
//</editor-fold desc="Constructor">
}