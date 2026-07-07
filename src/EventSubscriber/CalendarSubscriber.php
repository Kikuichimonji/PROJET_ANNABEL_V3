<?php

namespace App\EventSubscriber;

use App\Repository\ConsultCalendarRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;


class CalendarSubscriber implements EventSubscriberInterface
{
    private $CalendarRepository;
    private $router;

    public function __construct(ConsultCalendarRepository $CalendarRepository, UrlGeneratorInterface $router) 
    {
        $this->CalendarRepository = $CalendarRepository;
        $this->router = $router;
    }
    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar)
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();
        $filters = $calendar->getFilters();

        // Modify the query to fit to your entity and needs
        // Change booking.beginAt by your start date property
        $consultCalendar = $this->CalendarRepository
            ->createQueryBuilder('consultCalendar')
            ->where('consultCalendar.startDate BETWEEN :start and :end OR consultCalendar.endDate BETWEEN :start and :end')
            ->setParameter('start', $start->format('Y-m-d H:i:s'))
            ->setParameter('end', $end->format('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult()
        ;

        foreach ($consultCalendar as $consult) {
            // this create the events with your data (here booking data) to fill calendar
            $ConsultEvent = new Event(
                $consult->getTitle(),
                $consult->getStartDate(),
                $consult->getEndDate() // If the end date is null or not defined, a all day event is created.
            );

            /*
             * Add custom options to events
             *
             * For more information see: https://fullcalendar.io/docs/event-object
             * and: https://github.com/fullcalendar/fullcalendar/blob/master/src/core/options.ts
             */

            $ConsultEvent->setOptions([
                'backgroundColor' => '#3788d8',
                'borderColor' => '#3788d8',
            ]);
            $ConsultEvent->addOption(
                'url',
                $this->router->generate('consult_show', [
                    'id' => $consult->getId(),
                ])
            );

            // finally, add the event to the CalendarEvent to fill the calendar
            $calendar->addEvent($ConsultEvent);
        }
    }

}