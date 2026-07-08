<?php

namespace App\Tests\EventSubscriber;

use App\Entity\ConsultCalendar;
use App\EventSubscriber\CalendarSubscriber;
use App\Repository\ConsultCalendarRepository;
use CalendarBundle\Event\SetDataEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Verrouille le comportement de CalendarSubscriber::onCalendarSetData() :
 * un rendez-vous qui commence avant la fenetre visible de l'agenda et se
 * termine apres doit tout de meme apparaitre (bug corrige : la requete
 * d'origine ne couvrait que "commence dans la fenetre" ou "termine dans la
 * fenetre", pas "englobe toute la fenetre").
 */
class CalendarSubscriberTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    private function createEvent(string $title, \DateTime $start, \DateTime $end): void
    {
        $event = new ConsultCalendar();
        $event->setTitle($title);
        $event->setStartDate($start);
        $event->setEndDate($end);
        $this->entityManager->persist($event);
    }

    public function testEventSpanningTheWholeVisibleWindowIsNotLost(): void
    {
        $windowStart = new \DateTime('2026-03-10 00:00:00');
        $windowEnd = new \DateTime('2026-03-16 23:59:59');

        $this->createEvent(
            'Chevauche toute la semaine',
            new \DateTime('2026-03-05 09:00:00'), // avant la fenetre
            new \DateTime('2026-03-20 09:00:00')   // apres la fenetre
        );
        $this->createEvent(
            'Commence dans la fenetre',
            new \DateTime('2026-03-12 09:00:00'),
            new \DateTime('2026-03-12 10:00:00')
        );
        $this->createEvent(
            'Hors fenetre',
            new \DateTime('2026-01-01 09:00:00'),
            new \DateTime('2026-01-01 10:00:00')
        );
        $this->entityManager->flush();

        /** @var ConsultCalendarRepository $repository */
        $repository = $this->entityManager->getRepository(ConsultCalendar::class);
        $subscriber = new CalendarSubscriber($repository, self::getContainer()->get('router'));

        $setDataEvent = new SetDataEvent($windowStart, $windowEnd, []);
        $subscriber->onCalendarSetData($setDataEvent);

        $titles = array_map(static fn ($event) => $event->getTitle(), $setDataEvent->getEvents());
        sort($titles);

        $this->assertSame(['Chevauche toute la semaine', 'Commence dans la fenetre'], $titles);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        unset($this->entityManager);
    }
}
