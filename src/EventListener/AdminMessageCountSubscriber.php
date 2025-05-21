<?php

namespace App\EventListener;

use App\Repository\MessageRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class AdminMessageCountSubscriber implements EventSubscriberInterface
{
    private MessageRepository $messageRepository;

    public function __construct(MessageRepository $messageRepository)
    {
        $this->messageRepository = $messageRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event)
    {
        // Ne s'applique qu'aux routes d'administration
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (strpos($route, 'app_admin') === 0) {
            try {
                $unreadCount = $this->messageRepository->countUnreadMessages();
                $request->attributes->set('unreadCount', $unreadCount);
            } catch (\Exception $e) {
                // La table message n'existe peut-être pas encore (par exemple pendant les migrations)
                $request->attributes->set('unreadCount', 0);
            }
        }
    }
}
