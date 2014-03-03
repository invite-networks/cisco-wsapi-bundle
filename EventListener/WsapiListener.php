<?php
/*
 * This file is part of the Invite Wsapi Bundle
 * 
 * (c) Invite Networks Inc. <info@invitenetworks.com>
 *
 * For the full copyright and license information, 
 * please view the LICENSE file that was distributed 
 * with this source code.
 */
namespace Invite\Bundle\Cisco\WsapiBundle\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Bridge\Monolog\Logger;
use Invite\Bundle\Cisco\WsapiBundle\WsapiEvents;
use Invite\Bundle\Cisco\WsapiBundle\Event\WsapiProbingEvent;
use Invite\Bundle\Cisco\WsapiBundle\Event\WsapiStatusEvent;
use Invite\Bundle\Cisco\WsapiBundle\Event\WsapiUnregisterEvent;
use Invite\Bundle\Cisco\WsapiBundle\Cache\CacheManager;
use Invite\Component\Cisco\Wsapi\Model\XcdrListenerInterface;
use Invite\Component\Cisco\Wsapi\Request\WsapiRequestInterface;

/**
 * WsapiListener abstract class
 * 
 * Listens for Wsapi events from wsapi library and
 * sets the appropriate Symfony event.
 */
abstract class WsapiListener implements XcdrListenerInterface
{

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $dispatcher;

    /**
     * @var \Invite\Bundle\Cisco\WsapiBundle\Cache\CacheManager
     */
    protected $cm;

    /**
     * @var \Symfony\Bridge\Monolog\Logger
     */
    protected $logger;

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcher $dispatcher
     * @param \Invite\Bundle\Cisco\WsapiBundle\Cache\CacheManager $cm
     * @param \Symfony\Bridge\Monolog\Logger $logger
     */
    public function __construct(EventDispatcher $dispatcher, CacheManager $cm, Logger $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->cm = $cm;
        $this->logger = $logger;
    }

    /**
     * Dispatch Xcdr Probing Event.
     * 
     * @param array $data
     */
    public function processProbing(WsapiRequestInterface $probingRequest)
    {
        $probingEvent = new WsapiProbingEvent($probingRequest);
        $this->dispatcher->dispatch(
                WsapiEvents::WSAPI_PROBING, $probingEvent
        );

        if ($probingRequest->isValid()) {
            $regId = $probingRequest->getRegistrationId();
            $options = $probingRequest->getOptions();
            $cache = $this->cm->getCacheByRegId($regId);
            if ($cache) {
                $cache['ttl'] = $this->cm->getInterval() + 5;
                $this->cm->setCache($cache, $options);
            }
        }
    }

    /**
     * Dispatch Xcdr Status Event.
     * 
     * @param array $data
     */
    public function processStatus(WsapiRequestInterface $statusRequest)
    {
        $statusEvent = new WsapiStatusEvent($statusRequest);
        $this->dispatcher->dispatch(
                WsapiEvents::WSAPI_STATUS, $statusEvent
        );
    }

    /**
     * Dispatch Xcdr UnRegister Event.
     * 
     * @param array $data Must be md array with csv key.
     */
    public function processUnregister(WsapiRequestInterface $unregisterRequest)
    {
        $unregisterEvent = new WsapiUnregisterEvent($unregisterRequest);
        $this->dispatcher->dispatch(
                WsapiEvents::WSAPI_UNREGISTER, $unregisterEvent
        );
    }

}
