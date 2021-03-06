<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/3/18
 * Time: 10:39 AM
 */

namespace Tfboe\FmLib\Tests\Unit\Entity\Helpers;


use PHPUnit\Framework\MockObject\MockObject;
use Tfboe\FmLib\Entity\Helpers\UUIDEntity;
use Tfboe\FmLib\Tests\Helpers\UnitTestCase;


/**
 * Class BaseEntityTest
 * @package Tfboe\FmLib\Tests\Unit\Entity\Helpers
 */
class UUIDEntityTest extends UnitTestCase
{
//<editor-fold desc="Public Methods">
  /**
   * @covers \Tfboe\FmLib\Entity\Helpers\UUIDEntity::getId
   * @uses   \Tfboe\FmLib\Entity\Helpers\IdGenerator::createIdFrom
   */
  public function testId()
  {
    $entity = $this->mock();
    /** @noinspection PhpUnhandledExceptionInspection */
    self::getProperty(get_class($entity), 'id')->setValue($entity, 'test-id');
    self::assertEquals('test-id', $entity->getId());
  }

  /**
   * @covers \Tfboe\FmLib\Entity\Helpers\UUIDEntity::getEntityId
   * @uses   \Tfboe\FmLib\Entity\Helpers\IdGenerator::createIdFrom
   */
  public function testEntityId()
  {
    $entity = $this->mock();
    /** @noinspection PhpUnhandledExceptionInspection */
    self::getProperty(get_class($entity), 'id')->setValue($entity, 'test-id');
    self::assertEquals('test-id', $entity->getEntityId());
  }
//</editor-fold desc="Public Methods">

//<editor-fold desc="Private Methods">
  /**
   * @return MockObject|UUIDEntity
   */
  private function mock(): MockObject
  {
    return $this->getMockForTrait(UUIDEntity::class);
  }
//</editor-fold desc="Private Methods">
}