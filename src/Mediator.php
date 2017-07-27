<?php

namespace Noair;

/**
 * Main event pipeline.
 *
 * @author  Garrett Whitehorn
 * @author  David Tkachuk
 *
 * @version 1.0
 */
class Mediator implements Observable
{
    /**
     * @api
     *
     * @var array Holds any published events to which no handler has yet subscribed
     *
     * @since   1.0
     */
    public $held = [];

    /**
     * @internal
     *
     * @var bool Whether we should put published events for which there are no subscribers onto the list.
     *
     * @since   1.0
     */
    protected $holdingUnheardEvents = false;

    /**
     * @internal
     *
     * @var Manager
     *
     * @since 1.0
     */
    protected $mgr;

    /**
     *
     */
    public function __construct(Manager $mgr)
    {
        $this->mgr = $mgr;
    }

    /**
     * Registers event handler(s) to event name(s).
     *
     * @api
     *
     * @throws BadMethodCallException if validation of any handler fails
     *
     * @param array $eventHandlers Associative array of event names & handlers
     *
     * @return array The results of firing any held events
     *
     * @since   1.0
     *
     * @version 1.0
     */
    public function subscribe(array $eventHandlers)
    {
        $results = [];

        foreach ($eventHandlers as $eventName => $handler) {
            if (!self::isValidHandler($handler)) {
                throw new \BadMethodCallException('Mediator::subscribe() - invalid handler passed for ' . $eventName);
            }

            // extract interval (in milliseconds) from $eventName
            $interval = 0;
            if (strpos($eventName, 'timer:') === 0) {
                $interval = (int) substr($eventName, 6);
                $eventName = 'timer';
            }

            $this->mgr->add($eventName, $interval, $handler);

            // there will never be held timer events, but otherwise fire matching held events
            if ($interval === 0) {
                $results[] = $this->fireHeldEvents($eventName);
            }
        }

        return $results;
    }

    /**
     * Let any relevant subscribers know an event needs to be handled.
     *
     * Note: The event object can be used to share information to other similar event handlers.
     *
     * @api
     *
     * @param Event $event An event object, usually freshly created
     *
     * @return mixed Result of the event
     *
     * @since   1.0
     *
     * @version 1.0
     */
    public function publish(Event $event)
    {
        $event->mediator = $this;
        $result = null;

        // Make sure event is fired to any subscribers that listen to all events
        // all is greedy, any is not - due to order
        foreach (['all', $event->name, 'any'] as $eventName) {
            if ($this->mgr->hasSubscribers($eventName)) {
                $result = $this->mgr->fire($eventName, $event, $result);
            }
        }

        if ($result !== null) {
            return $result;
        }

        // If no subscribers were listening to this event, try holding it
        $this->tryHolding($event);
    }

    /**
     * Detach a given handler (or all) from an event name.
     *
     * @api
     *
     * @param array $eventHandlers Associative array of event names & handlers
     *
     * @return self This object
     *
     * @since   1.0
     *
     * @version 1.0
     */
    public function unsubscribe(array $eventHandlers)
    {
        foreach ($eventHandlers as $eventName => $callback) {
            if ($callback == '*') {
                // we're unsubscribing all of $eventName
                $this->mgr->remove([$eventName]);
                continue;
            }

            $callback = $this->formatCallback($eventName, $callback);

            // if this is a timer subscriber
            if (strpos($eventName, 'timer:') === 0) {
                // then we'll need to match not only the callback but also the interval
                $callback = [
                    'interval' => (int) substr($eventName, 6),
                    'callback' => $callback,
                ];
                $eventName = 'timer';
            }

            $this->mgr->searchAndDestroy($eventName, $callback);
        }

        return $this;
    }

    /**
     * Get or set the value of the holdingUnheardEvents property.
     *
     * @api
     *
     * @param bool|null $val true or false to set the value, omit to retrieve
     *
     * @return bool the value of the property
     *
     * @since   1.0
     *
     * @version 1.0
     */
    public function holdUnheardEvents($val = null)
    {
        if ($val === null) {
            return $this->holdingUnheardEvents;
        }

        $val = (bool) $val;
        if ($val === false) {
            $this->held = []; // make sure the held list is wiped clean
        }

        return ($this->holdingUnheardEvents = $val);
    }

    /**
     * If any events are held for $eventName, re-publish them now.
     *
     * @internal
     *
     * @param string $eventName The event name to check for
     *
     * @since   1.0
     *
     * @version 1.0
     */
    protected function fireHeldEvents($eventName)
    {
        $results = [];
        // loop through any held events
        foreach ($this->held as $i => $e) {
            // if this held event's name matches our new subscriber
            if ($e->getName() == $eventName) {
                // re-publish that matching held event
                $results[] = $this->publish(array_splice($this->held, $i, 1)[0]);
            }
        }

        return $results;
    }

    /**
     *
     */
    protected function formatCallback($eventName, $callback)
    {
        if (is_object($callback) && $callback instanceof Observer) {
            // assume we're unsubscribing a parsed method name
            $callback = [$callback, 'on' . str_replace(':', '', ucfirst($eventName))];
        }

        if (is_array($callback) && !is_callable($callback)) {
            // we've probably been given an Observer's handler array
            $callback = $callback[0];
        }

        if (!is_callable($callback)) {
            // callback is invalid, so halt
            throw new \InvalidArgumentException('Cannot unsubscribe a non-callable');
        }

        return $callback;
    }

    /**
     * Puts an event on the held list if enabled and not a timer.
     *
     * @internal
     *
     * @param Event $event The event object to be held
     *
     * @since   1.0
     *
     * @version 1.0
     */
    protected function tryHolding(Event $event)
    {
        if ($this->holdingUnheardEvents && $event->name != 'timer') {
            array_unshift($this->held, $event);
        }
    }

    /**
     *
     */
    protected static function isValidHandler($handler)
    {
        return (is_callable($handler[0])
                && (!isset($handler[1]) || is_int($handler[1]))
                && (!isset($handler[2]) || is_bool($handler[2]))
        );
    }
}
