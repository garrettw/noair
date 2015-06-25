<?php

namespace Noair;

/**
 * Noair observer class -- to be extended.
 *
 * @author  Garrett Whitehorn
 * @author  David Tkachuk
 *
 * @version 1.0
 */
abstract class Observer
{
    /**
     * @api
     *
     * @var Observable The mediator instance our handlers are registered with
     *
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
     *
     * @var array The event handlers we'll be subscribing
     *
     * @since   1.0
     */
    protected $handlers = [];

    /**
     * If calling subscribe() results in firing of pending events, this will
     * store the results of that event.
     *
     * @api
     *
     * @var array Results of firing pending events
     *
     * @since   1.0
     */
    protected $subResults = [];

    /**
     * @api
     *
     * @var bool Reflects whether we have subscribed to a Mediator instance
     *
     * @since   1.0
     */
    protected $subscribed = false;

    public function __construct(Observable $m)
    {
        $this->mediator = $m;
    }

    /**
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    /**
     * Registers our handlers with our Mediator instance.
     *
     * @api
     *
     * @throws \RuntimeException if there are no handlers to subscribe
     *
     * @param array|null $newhandler Individual handler to subscribe if needed
     *
     * @return self This observer object
     *
     * @since   1.0
     *
     * @version 1.0
     */
    public function subscribe($newhandler = null)
    {
        if (is_array($newhandler)) {
            $this->handlers = array_merge($this->handlers, $newhandler);
            $this->subResults = $this->mediator->subscribe($newhandler);
            $this->subscribed = true;

            return $this;
        }

        // get an array of the methods in the child class
        $methods = (new \ReflectionClass($this))->getMethods(\ReflectionMethod::IS_PUBLIC);
        // filter out any that don't begin with "on"
        $methods = array_filter($methods,
            function (\ReflectionMethod $m) { return (strpos($m->name, 'on') === 0); }
        );
        $autohandlers = [];

        foreach ($methods as $method) {
            //extract the event name from the method name
            $eventName = lcfirst(substr($method->name, 2));

            // if this is a timer handler, insert a colon before the interval
            if (strpos($eventName, 'timer') === 0) {
                $eventName = substr_replace($eventName, ':', 5, 0);
            }

            // add it to our list
            $autohandlers[$eventName] = [[$this, $method->name]];
        }

        $this->handlers = array_merge($autohandlers, $this->handlers);

        if (empty($this->handlers)) {
            throw new \RuntimeException(
                '$this->handlers[] is empty or $this has no on*() methods!');
        }

        $this->subResults = $this->mediator->subscribe($this->handlers);
        $this->subscribed = true;

        return $this;
    }

    /**
     * Unregisters our handlers.
     *
     * @api
     *
     * @return self This observer object
     *
     * @since   1.0
     *
     * @version 1.0
     */
    public function unsubscribe()
    {
        if (empty($this->handlers)) {
            return $this;
        }

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
