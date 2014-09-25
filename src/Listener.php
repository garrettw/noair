<?php

namespace Noair;

/**
 * Noair listener class -- to be extended
 *
 * @author  Garrett Whitehorn
 * @author  David Tkachuk
 * @package Noair
 * @version 1.0
 */
abstract class Listener
{
    /**
     * This can be set by child class constructors so that Noair can call
     * getEvents() and register the results. If this array is empty when the
     * listener is to be registered, Noair will analyze any on* method names and
     * register them automagically. However, doing it that way means you forfeit
     * the ability to give handlers priority and forceability.
     *
     * Terminology note: they're not subscribers until they're subscribed ;)
     *
     * @api
     * @var     array   The event handlers we'll be subscribing
     * @since   1.0
     */
    protected $handlers = [];

    /**
     * @api
     * @var     Noair   Our instance of Noair that our handlers are registered with
     * @since   1.0
     */
    protected $noair;

    /**
     * @api
     * @var     int The default priority to use when subscribing handlers with
     *              no explicit priority
     * @since   1.0
     */
    protected $defaultpriority = Noair::PRIORITY_NORMAL;

    /**
     * @api
     * @var     bool    Reflects whether we have subscribed to a Noair instance
     * @since   1.0
     */
    protected $subscribed = false;

    /**
     * First, get our handler list, and then find all on* methods in $this and
     * add them to the list.
     *
     * Caveat: using the latter paradigm, you lose the ability to give handlers
     * priority and forceability.
     *
     * @api
     * @return  array   our handler list
     * @since   1.0
     * @version 1.0
     */
    public function getHandlers()
    {
        $handlers = (array) $this->handlers;

        $methods = (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method):
            if (strpos($method->name, 'on') === 0):

                $eventName = lcfirst(substr($method->name, 2));

                if (strpos($eventName, 'timer') === 0):
                    $eventName = substr_replace($eventName, ':', 5, 0);

                elseif (in_array($eventName, array_column($handlers, 0))):
                    continue;
                endif;

                $handlers[] = [$eventName, [$this, $method->name]];
            endif;
        endforeach;
        return $handlers;
    }

    /**
     * Registers our handlers with a particular Noair instance
     *
     * @api
     * @param   Noair|null  $noair  The Noair instance we'll be using
     * @param   array|null  &$results   Used to return results of pending events
     * @throws  \RuntimeException   if there are no handlers to subscribe
     * @return  self    This listener object
     * @since   1.0
     * @version 1.0
     */
    public function subscribe(Noair $noair = null, &$results = null)
    {
        $handlers = $this->getHandlers();
        if (empty($handlers)):
            throw new \RuntimeException(
                '$this->handlers[] is empty or $this has no on* methods!');
        endif;

        if (isset($noair)):
            $this->noair = $noair;
        endif;

        if (isset($this->noair)):
            $this->noair->subscribe($handlers, null, $results, $this->defaultPriority);
            $this->subscribed = true;
        endif;

        return $this;
    }

    /**
     * Unregisters our handlers
     *
     * @api
     * @return  self    This listener object
     * @since   1.0
     * @version 1.0
     */
    public function unsubscribe()
    {
        if (isset($this->noair) && !empty($handlers = $this->getHandlers())):
            $this->noair->unsubscribe($handlers);
            $this->subscribed = false;
        endif;

        return $this;
    }
}
