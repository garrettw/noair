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
    const PRIORITY_URGENT = 0;
    const PRIORITY_HIGHEST = 1;
    const PRIORITY_HIGH = 2;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_LOW = 4;
    const PRIORITY_LOWEST = 5;

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
     * @var array Contains registered events and their handlers by priority
     *
     * @since   1.0
     */
    protected $subscribers = [];

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

            $this->addNewSub($eventName, $interval, $handler);

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
            if ($this->hasSubscribers($eventName)) {
                $result = $this->fireMatchingSubs($eventName, $event, $result);
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
                unset($this->subscribers[$eventName]);
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

            $this->searchAndDestroy($eventName, $callback);
        }

        return $this;
    }

    /**
     * Determine if the event name has any subscribers.
     *
     * @api
     *
     * @param string $eventName The desired event's name
     *
     * @return bool Whether or not the event was published
     *
     * @since   1.0
     *
     * @version 1.0
     */
    public function hasSubscribers($eventName)
    {
        return (isset($this->subscribers[$eventName])
                && count($this->subscribers[$eventName]) > 1);
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
     * Determine if the described event has been subscribed to or not by the callback.
     *
     * @api
     *
     * @param string   $eventName The desired event's name
     * @param callable $callback  The specific callback we're looking for
     *
     * @return int|false Subscriber's array index if found, false otherwise; use ===
     *
     * @since   1.0
     *
     * @version 1.0
     */
    public function isSubscribed($eventName, callable $callback)
    {
        return ($this->hasSubscribers($eventName))
            ? self::arraySearchDeep($callback, $this->subscribers[$eventName])
            : false;
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
     * Handles inserting the new subscriber into the sorted internal array.
     *
     * @internal
     *
     * @param string $eventName The event it will listen for
     * @param int    $interval  The timer interval, if it's a timer (0 if not)
     * @param array  $handler   Each individual handler coming from the Observer
     *
     * @since   1.0
     *
     * @version 1.0
     */
    protected function addNewSub($eventName, $interval, array $handler)
    {
        // scaffold if not exist
        if (!$this->hasSubscribers($eventName)) {
            $this->subscribers[$eventName] = [
                [ // insert positions
                    self::PRIORITY_URGENT => 1,
                    self::PRIORITY_HIGHEST => 1,
                    self::PRIORITY_HIGH => 1,
                    self::PRIORITY_NORMAL => 1,
                    self::PRIORITY_LOW => 1,
                    self::PRIORITY_LOWEST => 1,
                ]
            ];
        }

        switch (count($handler)) {
            case 1:
                $handler[] = self::PRIORITY_NORMAL;
                // fall through
            case 2:
                $handler[] = false;
        }

        $sub = [
            'callback' => $handler[0],
            'priority' => $priority = $handler[1],
            'force' => $handler[2],
            'interval' => $interval,
            'nextcalltime' => self::currentTimeMillis() + $interval,
        ];

        $insertpos = $this->subscribers[$eventName][0][$priority];
        array_splice($this->subscribers[$eventName], $insertpos, 0, [$sub]);

        $this->realignPriorities($eventName, $priority);
    }

    /**
     * Takes care of actually calling the event handling functions
     *
     * @internal
     *
     * @param string $eventName
     * @param Event  $event
     * @param mixed  $result
     *
     * @since   1.0
     *
     * @version 1.0
     */
    protected function fireMatchingSubs($eventName, Event $event, $result = null)
    {
        $subs = $this->subscribers[$eventName];
        unset($subs[0]);

        // Loop through the subscribers of this event
        foreach ($subs as $i => $subscriber) {

            // If the event's cancelled and the subscriber isn't forced, skip it
            if ($event->cancelled && $subscriber['force'] === false) {
                continue;
            }

            // If the subscriber is a timer...
            if ($subscriber['interval'] !== 0) {
                // Then if the current time is before when the sub needs to be called
                if (self::currentTimeMillis() < $subscriber['nextcalltime']) {
                    // It's not time yet, so skip it
                    continue;
                }

                // Mark down the next call time as another interval away
                $this->subscribers[$eventName][$i]['nextcalltime']
                    += $subscriber['interval'];
            }

            // Fire it and save the result for passing to any further subscribers
            $event->previousResult = $result;
            $result = call_user_func($subscriber['callback'], $event);
        }

        return $result;
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
     *
     */
    protected function realignPriorities($eventName, $priority, $inc = 1)
    {
        for ($prio = $priority; $prio <= self::PRIORITY_LOWEST; $prio++) {
            $this->subscribers[$eventName][0][$prio] += $inc;
        }
    }

    /**
     *
     * @param callable $callback
     */
    protected function searchAndDestroy($eventName, $callback)
    {
        // Loop through the subscribers for the matching event
        foreach ($this->subscribers[$eventName] as $key => $subscriber) {

            // if this subscriber doesn't match what we're looking for, keep looking
            if (self::arraySearchDeep($callback, $subscriber) === false) {
                continue;
            }

            // otherwise, cut it out and get its priority
            $priority = array_splice($this->subscribers[$eventName], $key, 1)[0]['priority'];

            // shift the insertion points up for equal and lower priorities
            $this->realignPriorities($eventName, $priority, -1);
        }

        // If there are no more events, remove the event
        if (!$this->hasSubscribers($eventName)) {
            unset($this->subscribers[$eventName]);
        }
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
     * Searches a multi-dimensional array for a value in any dimension.
     *
     * @internal
     *
     * @param mixed $needle   The value to be searched for
     * @param array $haystack The array
     *
     * @return int|bool The top-level key containing the needle if found, false otherwise
     *
     * @since   1.0
     *
     * @version 1.0
     */
    protected static function arraySearchDeep($needle, array $haystack)
    {
        if (is_array($needle)
            && !is_callable($needle)
            // and if all key/value pairs in $needle have exact matches in $haystack
            && count(array_diff_assoc($needle, $haystack)) == 0
        ) {
            // we found what we're looking for, so bubble back up with 'true'
            return true;
        }

        foreach ($haystack as $key => $value) {
            if ($needle === $value
                || (is_array($value) && self::arraySearchDeep($needle, $value) !== false)
            ) {
                // return top-level key of $haystack that contains $needle as a value somewhere
                return $key;
            }
        }
        // 404 $needle not found
        return false;
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

    /**
     * Returns the current timestamp in milliseconds.
     * Named for the similar function in Java.
     *
     * @internal
     *
     * @return int Current timestamp in milliseconds
     *
     * @since   1.0
     *
     * @version 1.0
     */
    final protected static function currentTimeMillis()
    {
        // microtime(true) returns a float where there's 4 digits after the
        // decimal and if you add 00 on the end, those 6 digits are microseconds.
        // But we want milliseconds, so bump that decimal point over 3 places.
        return (int) (microtime(true) * 1000);
    }
}
