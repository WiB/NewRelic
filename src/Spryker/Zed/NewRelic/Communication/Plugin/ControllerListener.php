<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\NewRelic\Communication\Plugin;

use Spryker\Shared\Kernel\Store;
use Spryker\Shared\Library\System;
use Spryker\Shared\NewRelic\NewRelicApiInterface;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @method \Spryker\Zed\NewRelic\Communication\NewRelicCommunicationFactory getFactory()
 */
class ControllerListener extends AbstractPlugin implements EventSubscriberInterface
{

    const PRIORITY = -255;

    /**
     * @var \Spryker\Shared\NewRelic\NewRelicApiInterface
     */
    protected $newRelicApi;

    /**
     * @var \Spryker\Shared\Kernel\Store
     */
    protected $store;

    /**
     * @var \Spryker\Shared\Library\System
     */
    protected $system;

    /**
     * @var array
     */
    protected $ignorableTransactions;

    /**
     * @param \Spryker\Shared\NewRelic\NewRelicApiInterface $newRelicApi
     * @param \Spryker\Shared\Kernel\Store $store
     * @param \Spryker\Shared\Library\System $system
     * @param array $ignorableTransactions
     */
    public function __construct(NewRelicApiInterface $newRelicApi, Store $store, System $system, array $ignorableTransactions = [])
    {
        $this->newRelicApi = $newRelicApi;
        $this->store = $store;
        $this->system = $system;
        $this->ignorableTransactions = $ignorableTransactions;
    }

    /**
     * @param \Symfony\Component\HttpKernel\Event\FilterControllerEvent $event
     *
     * @return void
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $transactionName = $this->getTransactionName($request);
        $requestUri = $request->server->get('REQUEST_URI', 'n/a');
        $host = $request->server->get('COMPUTERNAME', $this->system->getHostname());

        $this->newRelicApi->setNameOfTransaction($transactionName);
        $this->newRelicApi->addCustomParameter('request_uri', $requestUri);
        $this->newRelicApi->addCustomParameter('host', $host);
        $this->newRelicApi->addCustomParameter('store', $this->store->getStoreName());
        $this->newRelicApi->addCustomParameter('locale', $this->store->getCurrentLocale());

        if ($this->ignoreTransaction($transactionName)) {
            $this->newRelicApi->markIgnoreTransaction();
        }
    }

    /**
     * @param string $transaction
     *
     * @return bool
     */
    protected function ignoreTransaction($transaction)
    {
        foreach ($this->ignorableTransactions as $ignorableTransaction) {
            if (strpos($transaction, $ignorableTransaction) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return string
     */
    protected function getTransactionName(Request $request)
    {
        $module = $request->attributes->get('module');
        $controller = $request->attributes->get('controller');
        $action = $request->attributes->get('action');
        $transactionName = $module . '/' . $controller . '/' . $action;

        return $transactionName;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', static::PRIORITY],
        ];
    }

}
