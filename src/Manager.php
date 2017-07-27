<?php

namespace Noair;

/**
 * Repository for subscribers.
 *
 * @author  Garrett Whitehorn
 *
 * @version 1.0
 */
class Manager
{
    const PRIORITY_URGENT = 0;
    const PRIORITY_HIGHEST = 1;
    const PRIORITY_HIGH = 2;
    const PRIORITY_NORMAL = 3;
    const PRIORITY_LOW = 4;
    const PRIORITY_LOWEST = 5;

    /**
     * @internal
     *
     * @var array Contains registered events and their handlers by priority
     *
     * @since   1.0
     */
    protected $subscribers = [];

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
    public function add($eventName, $interval, array $handler)
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
                // no break
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

        $this->realign($eventName, $priority);
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
    public function fire($eventName, Event $event, $result = null)
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
    public function remove($eventName)
    {
        unset($this->subscribers[$eventName]);
    }

    /**
     *
     * @param callable $callback
     */
    public function searchAndDestroy($eventName, $callback)
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
            $this->realign($eventName, $priority, -1);
        }

        // If there are no more events, remove the event
        if (!$this->hasSubscribers($eventName)) {
            unset($this->subscribers[$eventName]);
        }
    }

    /**
     *
     */
    protected function realign($eventName, $priority, $inc = 1)
    {
        for ($prio = $priority; $prio <= self::PRIORITY_LOWEST; $prio++) {
            $this->subscribers[$eventName][0][$prio] += $inc;
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
