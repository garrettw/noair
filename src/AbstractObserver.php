<?php

namespace Noair;

/**
 * Noair observer class -- to be extended
 *
 * @author  Garrett Whitehorn
 * @author  David Tkachuk
 * @package Noair
 * @version 1.0
 */
abstract class AbstractObserver implements ObserverInterface
{
    /**
     * @api
     * @var     Mediator   The Mediator instance our handlers are registered with
     * @since   1.0
     */
    protected $mediator;

    /**
     * This can be set by child classes to explicitly define each handler's
     * function name, priority, and/or forceability.
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
     * @var     bool    Reflects whether we have subscribed to a Mediator instance
     * @since   1.0
     */
    protected $subscribed = false;

    public function __construct(Mediator $m) {
        $this->mediator = $m;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Registers our handlers with our Mediator instance
     *
     * @api
     * @throws  \RuntimeException   if there are no handlers to subscribe
     * @return  array|null  Results of any pending events
     * @since   1.0
     * @version 1.0
     */
    public function subscribe()
    {
        // get an array of the methods in the child class
        $methods = (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC);
        // filter out any that don't begin with "on"
        $methods = array_filter($methods,
            function($m) { return (strpos($m->name, 'on') === 0); }
        );
        $autohandlers = [];

        foreach ($methods as $method):
            //extract the event name from the method name
            $eventName = lcfirst(substr($method->name, 2));

            // if this is a timer handler, insert a colon before the interval
            if (strpos($eventName, 'timer') === 0):
                $eventName = substr_replace($eventName, ':', 5, 0);
            endif;

            // add it to our list
            $autohandlers[$eventName] = [[$this, $method->name]];
        endforeach;

        $this->handlers = array_merge($autohandlers, $this->handlers);

        if (empty($this->handlers)):
            throw new \RuntimeException(
                '$this->handlers[] is empty or $this has no on*() methods!');
        endif;

        $this->mediator->subscribe($this->handlers, null, $results);
        $this->subscribed = true;

        return $results;
    }

    /**
     * Unregisters our handlers
     *
     * @api
     * @return  self    This observer object
     * @since   1.0
     * @version 1.0
     */
    public function unsubscribe()
    {
        if (empty($this->handlers)):
            return $this;
        endif;

        $this->mediator->unsubscribe($this->handlers);

        // filter out auto-handlers so that a subsequent call to subscribe()
        // works predictably
        $this->handlers = array_filter($this->handlers, function ($v) {
            return (strpos($v[0][1], 'on') !== 0);
        });

        $this->subscribed = false;

        return $this;
    }
}