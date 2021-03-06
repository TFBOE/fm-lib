<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 10/1/17
 * Time: 12:52 PM
 */

namespace Tfboe\FmLib\Tests\Unit\Entity\Helpers;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\MockObject\MockObject;
use Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity;
use Tfboe\FmLib\Entity\RankingSystemInterface;
use Tfboe\FmLib\Tests\Entity\RankingSystem;
use Tfboe\FmLib\Tests\Helpers\UnitTestCase;

/**
 * Class BaseEntityTest
 * @package Tfboe\FmLib\Tests\Unit\Entity\Helpers
 */
class TournamentHierarchyEntityTest extends UnitTestCase
{
//<editor-fold desc="Public Methods">
  /**
   * @covers \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testConstructor()
  {
    $entity = $this->entity();
    self::assertInstanceOf(Collection::class, $entity->getRankingSystems());
    self::assertEquals(0, $entity->getRankingSystems()->count());
  }

  /**
   * @covers \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   */
  public function testRankingSystems()
  {
    $entity = $this->entity();
    /** @var $system RankingSystemInterface */
    $system = $this->createStubWithId(RankingSystem::class);
    $entity->getRankingSystems()->set($system->getId(), $system);
    self::assertEquals(1, $entity->getRankingSystems()->count());
    self::assertEquals($system, $entity->getRankingSystems()[$system->getId()]);
  }
//</editor-fold desc="Public Methods">

//<editor-fold desc="Private Methods">
  /**
   * @return MockObject|TournamentHierarchyEntity
   */
  private function entity(): MockObject
  {
    return $this->getMockForAbstractClass(TournamentHierarchyEntity::class);
  }
//</editor-fold desc="Private Methods">
}