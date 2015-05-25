<?php

namespace Noair;

/**
 * Main event pipeline
 *
 * @author  Garrett Whitehorn
 * @author  David Tkachuk
 * @package Noair
 * @version 1.0
 */
class Mediator implements Observable
{
    const PRIORITY_URGENT	= 0;
    const PRIORITY_HIGHEST	= 1;
    const PRIORITY_HIGH		= 2;
    const PRIORITY_NORMAL	= 3;
    const PRIORITY_LOW		= 4;
    const PRIORITY_LOWEST	= 5;

    /**
     * @api
     * @var     array   Contains registered events and their handlers by priority
     * @since   1.0
     */
    protected $subscribers = [];

    /**
     * @api
     * @var     array   Holds any published events to which no handler has yet subscribed
     * @since   1.0
     */
    protected $pending = [];

    /**
     * @api
     * @var     bool    Whether we should put published events for which there are
     *                  no subscribers onto the $pending list.
     * @since   1.0
     */
    protected $holdUnheardEvents = false;

    /**
     * Constructor which can enable pending events functionality
     *
     * @api
     * @param   bool    $hold   Whether to enable pending events
     * @since   1.0
     * @version 1.0
     */
    public function __construct($hold = false)
    {
        $this->holdUnheardEvents = (bool) $hold;
    }

    /**
     * @return  mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $val)
    {
        if ($name == 'holdUnheardEvents'):
            if (!$val):
                // make sure the pending list is wiped clean
                $this->pending = [];
            endif;
            $this->holdUnheardEvents = (bool) $val;
        endif;
    }

    /**
     * Determine if the event name has any subscribers
     *
     * @api
     * @param   string  $eventName  The desired event's name
     * @return  bool    Whether or not the event was published
     * @since   1.0
     * @version 1.0
     */
    public function hasSubscribers($eventName)
    {
        return (isset($this->subscribers[$eventName])
                && $this->subscribers[$eventName]['subscribers'] > 0);
    }

    /**
     * Determine if the described event has been subscribed to or not by the callback
     *
     * @api
     * @param   string      $eventName  The desired event's name
     * @param   callable    $callback   The specific callback we're looking for
     * @return  int|false   Priority it's subscribed to if found, false otherwise; use ===
     * @since   1.0
     * @version 1.0
     */
    public function isSubscribed($eventName, callable $callback)
    {
        return ($this->hasSubscribers($eventName))
            ? self::arraySearchDeep($callback, $this->subscribers[$eventName])
            : false;
    }

    /**
     * Registers an event handler to an event
     *
     * @api
     * @param   string|array    $eventName  Event name to subscribe to, or
     *                                      an array of subscriber data
     * @param   callable|null   $callback   A callback that will handle the event
     * @param   array|null  &$results   Used to return results of pending events
     * @param   int         $priority   Priority of the handler (0-5)
     * @param   bool        $force      Whether to ignore event cancellation
     * @return  self    This object
     * @since   1.0
     * @version 1.0
     */
    public function subscribe($eventName, callable $callback = null,
                              &$results = null, $priority = self::PRIORITY_NORMAL,
                              $force = false)
    {
        if (!isset($results)):
            $results = [];
        endif;

        // handle an array of subscribers recursively if that's what we're given
        if (is_array($eventName)):
            foreach ($eventName as $event => $newsub):
                $this->subscribe($event, $newsub[0], $results,
                    (isset($newsub[1]) ? $newsub[1] : $priority),
                    (isset($newsub[2]) ? $newsub[2] : $force));
            endforeach;
            return $this;
        endif;

        // otherwise, we're not processing an array, so $callback better not be null
        if ($callback === null):
            throw new \BadMethodCallException('$this->subscribe() parameter 1 (callback) missing');
        endif;

        $interval = false;
        // if this is a timer subscriber
        if (strpos($eventName, 'timer:') === 0):
            // extract the desired firing interval from the name
            $interval = (int) substr($eventName, 6);
            $eventName = 'timer';
        endif;

        // If the event was never registered, create it
        if (!$this->hasSubscribers($eventName)):
            $this->subscribers[$eventName] = [
                'subscribers'          => 0,
                self::PRIORITY_URGENT  => [],
                self::PRIORITY_HIGHEST => [],
                self::PRIORITY_HIGH    => [],
                self::PRIORITY_NORMAL  => [],
                self::PRIORITY_LOW     => [],
                self::PRIORITY_LOWEST  => [],
            ];
        endif;

        $isInterval = ($interval !== false);
        // Our new subscriber will have these properties, at least
        // and if it's a timer, it will have a few more
        $newsub = [
            'callback' => $callback,
            'force'    => (bool) $force,
            'interval' => ($isInterval) ? $interval : null, // milliseconds
            'nextcalltime' => ($isInterval) ? self::currentTimeMillis() + $interval : null,
        ];
        // ok, now we've composed our subscriber, so throw it on the queue
        $this->subscribers[$eventName][$priority][] = $newsub;
        // and increment the counter for this event name
        $this->subscribers[$eventName]['subscribers']++;

        // there will never be pending timer events, so just return
        if ($isInterval):
            return $this;
        endif;

        // loop through any pending events
        foreach ($this->pending as $i => $e):
            // if this pending event's name matches our new subscriber
            if ($e->getName() == $eventName):
                // re-publish that matching pending event
                $results[] = $this->publish(array_splice($this->pending, $i, 1)[0], $priority);
            endif;
        endforeach;

        return $this;
    }

    /**
     * Detach a given handler (or all) from an event name
     *
     * @api
     * @param   string|array    $eventName  The event(s) we want to unsubscribe from
     * @param   callable|object|null    $callback   The callback we want to remove from the event
     * @return  self    This object
     * @since   1.0
     * @version 1.0
     */
    public function unsubscribe($eventName, $callback = null)
    {
        if (is_array($eventName)):
            foreach ($eventName as $event => $subscriber):
                if (is_array($subscriber)):
                    // handle an array of subscribers recursively if that's what we're given
                    $this->unsubscribe($event, $subscriber[0]);
                else:
                    // we're unsubscribing all from $eventName's events
                    $this->unsubscribe($event);
                endif;
            endforeach;

            return $this;
        endif;

        if ($callback === null):
            // we're unsubscribing all of $eventName
            unset($this->subscribers[$eventName]);
            return $this;
        endif;

        if (is_object($callback) && $callback instanceof AbstractObserver):
            // assume we're unsubscribing a parsed method name
            $callback = [$callback, 'on' . str_replace(':', '', ucfirst($eventName))];
        endif;

        if (!is_callable($callback)):
            // callback is invalid, so halt
            throw new \InvalidArgumentException('Cannot unsubscribe a non-callable');
        endif;

        // if this is a timer subscriber
        if (strpos($eventName, 'timer:') === 0):
            // then we'll need to match not only the callback but also the interval
            $callback = [
                'interval' => (int) substr($eventName, 6),
                'callback' => $callback
            ];
            $eventName = 'timer';
        endif;

        // If the event has been subscribed to by this callback
        if (($priority = $this->isSubscribed($eventName, $callback)) !== false):

            // Loop through the subscribers for the matching priority level
            foreach ($this->subscribers[$eventName][$priority] as $key => $subscriber):

                // if this subscriber matches what we're looking for
                if (self::arraySearchDeep($callback, $subscriber) !== false):

                    // delete that subscriber and decrement the event name's counter
                    unset($this->subscribers[$eventName][$priority][$key]);
                    $this->subscribers[$eventName]['subscribers']--;
                endif;
            endforeach;

            // If there are no more events, remove the event
            if (!$this->hasSubscribers($eventName)):
                unset($this->subscribers[$eventName]);
            endif;
        endif;

        return $this;
    }

    /**
     * Let any relevant subscribers know an event needs to be handled
     *
     * Note: The event object can be used to share information to other similar
     * event handlers.
     *
     * @api
     * @param   Event       $event  An event object, usually freshly created
     * @param   int|null    $priority   Notify only subscribers of a certain priority level
     * @return  mixed   Result of the event
     * @since   1.0
     * @version 1.0
     */
    public function publish(Event $event, $priority = null)
    {
        $event->mediator = $this;
        $eventNames = [];

        // Make sure event is fired to any subscribers that listen to all events
        if (isset($this->subscribers['all'])):
            $eventNames[] = 'all'; // all is greedy, any is not
        endif;

        if ($this->hasSubscribers($event->name)):
            $eventNames[] = $event->name;
        endif;

        if (isset($this->subscribers['any'])):
            $eventNames[] = 'any';
        endif;

        // If no subscribers are listening to this event, try holding it
        if (empty($eventNames)):
            $this->tryHolding($event);
            return;
        endif;

        $result = null;

        foreach ($eventNames as $eventName):
            // Loop through all the subscriber priority levels
            foreach ($this->subscribers[$eventName] as $plevel => &$subscribers):

                // If a priority was passed and this isn't it,
                // or if this isn't a subscriber array
                if (($priority !== null && $plevel != $priority) || !is_array($subscribers)):
                    // then move on to the next priority level
                    continue;
                endif;

                // Loop through the subscribers of this priority level
                foreach ($subscribers as &$subscriber):

                    // If the event's cancelled and the subscriber isn't forced, skip it
                    if ($event->cancelled && !$subscriber['force']):
                        continue;
                    endif;

                    // If the subscriber is a timer...
                    if (isset($subscriber['interval'])):
                        // Then if the current time is before when the sub needs to be called
                        if (self::currentTimeMillis() < $subscriber['nextcalltime']):
                            // It's not time yet, so skip it
                            continue;
                        endif;

                        // Mark down the next call time as another interval away
                        $subscriber['nextcalltime'] += $subscriber['interval'];
                    endif;

                    if (!is_callable($subscriber['callback'])):
                        throw new \BadFunctionCallException("Callback for $eventName is not valid");
                    endif;

                    // Fire it and save the result for passing to any further subscribers
                    $event->previousResult = $result;
                    $result = call_user_func($subscriber['callback'], $event);
                endforeach;
            endforeach;
        endforeach;

        return $result;
    }

    /**
     * Puts an event on the pending list if enabled and not a timer
     *
     * @internal
     * @param   Event   $event   The event object to be held
     * @since   1.0
     * @version 1.0
     */
    protected function tryHolding(Event $event)
    {
        if ($this->holdUnheardEvents && $event->name != 'timer'):
            array_unshift($this->pending, $event);
        endif;
    }

    /**
     * Searches a multi-dimensional array for a value in any dimension.
     *
     * @internal
     * @param   mixed   $needle     The value to be searched for
     * @param   array   $haystack   The array
     * @return  int|bool    The top-level key containing the needle if found, false otherwise
     * @since   1.0
     * @version 1.0
     */
    final protected static function arraySearchDeep($needle, array $haystack)
    {
        if (is_array($needle)
            && !is_callable($needle)
            // and if all key/value pairs in $needle have exact matches in $haystack
            && count(array_diff_assoc($needle, $haystack)) == 0
        ):
            // we found what we're looking for, so bubble back up with 'true'
            return true;
        endif;

        foreach ($haystack as $key => $value):
            if ($needle === $value
                || (is_array($value) && self::arraySearchDeep($needle, $value) !== false)
            ):
                // return top-level key of $haystack that contains $needle as a value somewhere
                return $key;
            endif;
        endforeach;
        // 404 $needle not found
        return false;
    }

    /**
     * Returns the current timestamp in milliseconds.
     * Named for the similar function in Java.
     *
     * @internal
     * @return  int Current timestamp in milliseconds
     * @since   1.0
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
