<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: benedikt
 * Date: 1/3/18
 * Time: 3:53 PM
 */

namespace Tfboe\FmLib\Tests\Unit\Service;

use Doctrine\ORM\EntityManagerInterface;
use Tfboe\FmLib\Entity\LastRecalculationInterface;
use Tfboe\FmLib\Service\DynamicServiceLoadingService;
use Tfboe\FmLib\Service\DynamicServiceLoadingServiceInterface;
use Tfboe\FmLib\Service\RankingSystem\RankingSystemInterface;
use Tfboe\FmLib\Service\RankingSystemService;
use Tfboe\FmLib\Tests\Entity\Competition;
use Tfboe\FmLib\Tests\Entity\Game;
use Tfboe\FmLib\Tests\Entity\Match;
use Tfboe\FmLib\Tests\Entity\Phase;
use Tfboe\FmLib\Tests\Entity\RankingSystem;
use Tfboe\FmLib\Tests\Entity\Tournament;
use Tfboe\FmLib\Tests\Helpers\UnitTestCase;


/**
 * Class EloRankingTest
 * @packageTfboe\FmLib\Tests\Unit\Service
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RankingSystemServiceTest extends UnitTestCase
{
//<editor-fold desc="Public Methods">
  /**
   * @covers \Tfboe\FmLib\Service\RankingSystemService::adaptOpenSyncFromValues
   * @uses   \Tfboe\FmLib\Service\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystemService::getRankingSystems
   * @uses   \Tfboe\FmLib\Service\RankingSystemService::getRankingSystemsEarliestInfluences
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testAdaptOpenSyncFromValues()
  {
    $serviceLoader = $this->createMock(DynamicServiceLoadingService::class);
    $serviceLoader->expects(self::exactly(2))
      ->method("loadRankingSystemService")
      ->willReturnCallback(function ($earliestInfluence) {
        $mock = $this->createMock(\Tfboe\FmLib\Service\RankingSystem\RankingSystemService::class);
        $mock->method("getEarliestInfluence")->willReturn(new \DateTime($earliestInfluence));
        return $mock;
      });
    /** @var DynamicServiceLoadingService $serviceLoader */
    /** @noinspection PhpParamsInspection */
    $service = new RankingSystemService($serviceLoader,
      $this->getMockForAbstractClass(EntityManagerInterface::class));


    $tournament = new Tournament();
    $ranking = $this->createStubWithId(RankingSystem::class, 'r1');
    $ranking->method('getServiceName')->willReturn("2017-01-01");
    $ranking->method('getOpenSyncFrom')->willReturn(new \DateTime("2017-01-01 15:00:00"));
    $ranking->expects(self::once())->method('setOpenSyncFrom')->with(new \DateTime("2017-01-01"));
    /** @var \Tfboe\FmLib\Entity\RankingSystemInterface $ranking */
    $tournament->getRankingSystems()->set($ranking->getId(), $ranking);

    $ranking2 = $this->createStubWithId(RankingSystem::class, 'r2');
    $ranking2->method('getServiceName')->willReturn("2017-02-01");
    $ranking2->method('getOpenSyncFrom')->willReturn(new \DateTime("2017-01-30 15:00:00"));
    $ranking2->expects(self::once())->method('setOpenSyncFrom')->with(new \DateTime("2017-01-30"));
    /** @var \Tfboe\FmLib\Entity\RankingSystemInterface $ranking2 */
    $tournament->getRankingSystems()->set($ranking2->getId(), $ranking2);

    $ranking3 = $this->createStubWithId(RankingSystem::class, 'r3');
    $ranking3->method('getOpenSyncFrom')->willReturn(null);
    $ranking3->expects(self::once())->method('setOpenSyncFrom')->with(new \DateTime("2017-03-01"));
    $ranking4 = $this->createStubWithId(RankingSystem::class, 'r4');
    $ranking4->method('getOpenSyncFrom')->willReturn(new \DateTime("2017-04-01"));
    $ranking4->expects(self::never())->method('setOpenSyncFrom');

    $service->adaptOpenSyncFromValues($tournament, [
      'r1' => ["rankingSystem" => $ranking, "earliestInfluence" => new \DateTime("2017-01-02")],
      'r2' => ["rankingSystem" => $ranking2, "earliestInfluence" => new \DateTime("2017-01-30")],
      'r3' => ["rankingSystem" => $ranking3, "earliestInfluence" => new \DateTime("2017-03-01")],
      'r4' => ["rankingSystem" => $ranking4, "earliestInfluence" => new \DateTime("2017-06-01")],
    ]);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystemService::applyRankingSystems
   * @covers \Tfboe\FmLib\Service\RankingSystemService::getRankingSystems
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Service\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testApplyRankingSystems()
  {
    $tournament = new Tournament();
    /** @var \Tfboe\FmLib\Entity\RankingSystemInterface $ranking2 */
    $ranking2 = $this->createStubWithId(RankingSystem::class, 's2');
    $tournament->getRankingSystems()->set($ranking2->getId(), $ranking2);
    /** @var \Tfboe\FmLib\Entity\RankingSystemInterface $ranking3 */
    $ranking3 = $this->createStubWithId(RankingSystem::class, 's3');

    $tournament->getRankingSystems()->set($ranking3->getId(), $ranking3);

    /** @var \Tfboe\FmLib\Entity\RankingSystemInterface $ranking4 */
    $ranking4 = $this->createStubWithId(RankingSystem::class, 's4');

    $oldInfluences = [
      $ranking2->getId() => ["rankingSystem" => $ranking2, "earliestInfluence" => new \DateTime("2017-02-01")],
      $ranking4->getId() => ["rankingSystem" => $ranking4, "earliestInfluence" => new \DateTime("2017-04-01")]
    ];

    $serviceLoader = $this->createMock(DynamicServiceLoadingService::class);
    $mock = $this->createMock(\Tfboe\FmLib\Service\RankingSystem\RankingSystemService::class);
    $mock->expects(self::exactly(3))->method("updateRankingForTournament")->withConsecutive(
      [$ranking2, $tournament, self::equalTo(new \DateTime("2017-02-01"))],
      [$ranking4, $tournament, self::equalTo(new \DateTime("2017-04-01"))],
      [$ranking3, $tournament, null]
    );
    $serviceLoader->expects(self::exactly(3))
      ->method("loadRankingSystemService")
      ->willReturn($mock);

    /** @var DynamicServiceLoadingService $serviceLoader */
    /** @noinspection PhpParamsInspection */
    $service = new RankingSystemService($serviceLoader,
      $this->getMockForAbstractClass(EntityManagerInterface::class));
    $service->applyRankingSystems($tournament, $oldInfluences);
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystemService::__construct
   */
  public function testConstruct()
  {
    $dsls = $this->getMockForAbstractClass(DynamicServiceLoadingServiceInterface::class);
    $entityManager = $this->getMockForAbstractClass(EntityManagerInterface::class);
    /** @var DynamicServiceLoadingServiceInterface $dsls */
    /** @var EntityManagerInterface $entityManager */
    $system = new RankingSystemService($dsls, $entityManager);
    self::assertInstanceOf(RankingSystemService::class, $system);
    /** @noinspection PhpUnhandledExceptionInspection */
    self::assertEquals($entityManager, self::getProperty(get_class($system), 'entityManager')->getValue($system));
    /** @noinspection PhpUnhandledExceptionInspection */
    self::assertEquals($dsls, self::getProperty(get_class($system), 'dsls')->getValue($system));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystemService::getRankingSystemsEarliestInfluences
   * @covers \Tfboe\FmLib\Service\RankingSystemService::getRankingSystems
   * @uses   \Tfboe\FmLib\Entity\Traits\Competition
   * @uses   \Tfboe\FmLib\Entity\Traits\Competition
   * @uses   \Tfboe\FmLib\Entity\Traits\Game
   * @uses   \Tfboe\FmLib\Entity\Traits\Game
   * @uses   \Tfboe\FmLib\Entity\Traits\Match
   * @uses   \Tfboe\FmLib\Entity\Traits\Match
   * @uses   \Tfboe\FmLib\Entity\Traits\Phase
   * @uses   \Tfboe\FmLib\Entity\Traits\Phase
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Traits\Tournament
   * @uses   \Tfboe\FmLib\Entity\Helpers\NameEntity
   * @uses   \Tfboe\FmLib\Service\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Service\RankingSystem\RankingSystemService::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::__construct
   * @uses   \Tfboe\FmLib\Entity\Helpers\TournamentHierarchyEntity::getRankingSystems
   */
  public function testGetRankingSystemsEarliestInfluences()
  {
    $serviceLoader = $this->createMock(DynamicServiceLoadingService::class);
    $serviceLoader->expects(self::exactly(3))
      ->method("loadRankingSystemService")
      ->willReturnCallback(function ($earliestInfluence) {
        $mock = $this->createMock(\Tfboe\FmLib\Service\RankingSystem\RankingSystemService::class);
        $mock->method("getEarliestInfluence")->willReturn(new \DateTime($earliestInfluence));
        return $mock;
      });
    /** @var DynamicServiceLoadingService $serviceLoader */
    /** @noinspection PhpParamsInspection */
    $service = new RankingSystemService($serviceLoader,
      $this->getMockForAbstractClass(EntityManagerInterface::class));
    $tournament = new Tournament();
    $ranking2 = $this->createStubWithId(RankingSystem::class, 'r2');
    $ranking2->method('getServiceName')->willReturn("2017-04-01");
    /** @var \Tfboe\FmLib\Entity\RankingSystemInterface $ranking2 */
    $tournament->getRankingSystems()->set($ranking2->getId(), $ranking2);

    $competition = new Competition();
    $competition->setName("TestCompetition")->setTournament($tournament);
    $phase = new Phase();
    $phase->setPhaseNumber(1);
    $phase->setCompetition($competition);
    $ranking3 = $this->createStubWithId(RankingSystem::class, 'r3');
    $ranking3->method('getServiceName')->willReturn("2017-02-01");
    /** @var \Tfboe\FmLib\Entity\RankingSystemInterface $ranking3 */

    $phase->getRankingSystems()->set($ranking3->getId(), $ranking3);

    $match = new Match();
    $match->setMatchNumber(1);
    $match->setPhase($phase);
    $game = new Game();
    $game->setGameNumber(1);
    $game->setMatch($match);
    $ranking4 = $this->createStubWithId(RankingSystem::class, 'r4');
    $ranking4->method('getServiceName')->willReturn("2017-03-01");
    /** @var \Tfboe\FmLib\Entity\RankingSystemInterface $ranking4 */
    $game->getRankingSystems()->set($ranking4->getId(), $ranking4);


    self::assertEquals(
      [
        $ranking2->getId() => ["rankingSystem" => $ranking2, "earliestInfluence" => new \DateTime("2017-04-01")],
        $ranking3->getId() => ["rankingSystem" => $ranking3, "earliestInfluence" => new \DateTime("2017-02-01")],
        $ranking4->getId() => ["rankingSystem" => $ranking4, "earliestInfluence" => new \DateTime("2017-03-01")],
      ],
      $service->getRankingSystemsEarliestInfluences($tournament));
  }

  /**
   * @covers \Tfboe\FmLib\Service\RankingSystemService::recalculateRankingSystems
   * @uses   \Tfboe\FmLib\Service\RankingSystemService::__construct
   */
  public function testRecalculateRankingSystems()
  {
    $rs1 = $this->createMock(\Tfboe\FmLib\Entity\RankingSystemInterface::class);
    $rs1->expects(self::once())->method('getServiceName')->willReturn('service');
    $rs1->expects(self::exactly(3))->method('getOpenSyncFrom')->willReturn(new \DateTime("2017-02-01"));
    $rs1->expects(self::once())->method('setOpenSyncFrom')->with(null);
    $rs1->expects(self::once())->method('setOpenSyncFromInProcess')->with(null);
    $rs2 = $this->createMock(\Tfboe\FmLib\Entity\RankingSystemInterface::class);
    $rs2->expects(self::once())->method('getServiceName')->willReturn('service');
    $rs2->expects(self::exactly(3))->method('getOpenSyncFrom')->willReturn(new \DateTime("2017-05-01"));
    $rs2->expects(self::once())->method('setOpenSyncFrom')->with(null);
    $rs2->expects(self::once())->method('setOpenSyncFromInProcess')->with(null);
    $slash = '\\';
    $first = 'SELECT s';
    $second = ' FROM Tfboe';
    $third = 'FmLib';
    $rest = 'RankingSystemInterface s WHERE s.openSyncFrom IS NOT NULL OR s.openSyncFromInProcess IS NOT NULL';
    $entityManager = $this->getEntityManagerMockForQuery([$rs1, $rs2],
      $first . $second . $slash . $third . $slash . 'Entity' . $slash . $rest, ['flush', 'clear', 'transactional',
        'find']);
    $entityManager->method('transactional')->willReturnCallback(function ($f) use ($entityManager) {
      return $f($entityManager);
    });
    $lastRecalculation = $this->createMock(LastRecalculationInterface::class);
    $entityManager->method('find')->willReturn($lastRecalculation);
    $dsls = $this->getMockForAbstractClass(DynamicServiceLoadingServiceInterface::class);
    $service = $this->getMockForAbstractClass(RankingSystemInterface::class);
    $service->expects(self::exactly(2))->method('updateRankingFrom')
      ->withConsecutive([$rs1, new \DateTime("2017-02-01")], [$rs2, new \DateTime("2017-05-01")]);
    $dsls->expects(self::exactly(2))->method('loadRankingSystemService')->with('service')->willReturn($service);
    /** @var DynamicServiceLoadingServiceInterface $dsls */
    /** @var EntityManagerInterface $entityManager */
    $system = new RankingSystemService($dsls, $entityManager);
    $system->recalculateRankingSystems();
  }
//</editor-fold desc="Public Methods">
}