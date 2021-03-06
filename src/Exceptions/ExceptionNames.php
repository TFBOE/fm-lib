<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 10/21/17
 * Time: 8:44 AM
 */

namespace Tfboe\FmLib\Exceptions;


use Tfboe\FmLib\Helpers\BasicEnum;

/**
 * Class ExceptionNames
 * @package Tfboe\FmLib\Exceptions
 */
abstract class ExceptionNames extends BasicEnum
{
//<editor-fold desc="Fields">
  const AUTHENTICATION_EXCEPTION = "AuthenticationException";
  const DUPLICATE_EXCEPTION = "DuplicateException";
  const INTERNAL_EXCEPTION = "InternalException";
  const PLAYER_ALREADY_EXISTS_EXCEPTION = "PlayerAlreadyExistsException";
  const REFERENCE_EXCEPTION = "ReferenceException";
  const UNORDERED_PHASE_NUMBER_EXCEPTION = "UnorderedPhaseNumberException";
  const VALIDATION_EXCEPTION = "ValidationException";
//</editor-fold desc="Fields">
}