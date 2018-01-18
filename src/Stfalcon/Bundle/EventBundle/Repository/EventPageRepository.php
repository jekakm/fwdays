<?php

namespace Stfalcon\Bundle\EventBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Stfalcon\Bundle\EventBundle\Entity\Event;

/**
 * EventPageRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventPageRepository extends EntityRepository
{
    /**
     * Get event pages that may show in menu
     *
     * @param $event
     */
    public function getEventPagesShowInMenu(Event $event)
    {
        $this->createQueryBuilder('ep')
            ->where('ep.event  = :event')
            ->where('ep.showInMenu = :show')
            ->setParameter('event', $event)
            ->setParameter('show', true)
            ->getQuery()
            ->getResult();
    }
}