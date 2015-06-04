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
     * @var     array   Holds any published events to which no handler has yet subscribed
     * @since   1.0
     */
    public $held = [];

    /**
     * @internal
     * @var     bool    Whether we should put published events for which there are no subscribers onto the $held list.
     * @since   1.0
     */
    protected $holdingUnheardEvents = false;

    /**
     * @internal
     * @var     array   Contains registered events and their handlers by priority
     * @since   1.0
     */
    protected $subscribers = [];

    /**
     * Registers event handler(s) to event name(s)
     *
     * @api
     * @throws  BadMethodCallException  if validation of any handler fails
     * @param   array   $eventHandlers  Associative array of event names & handlers
     * @return  array   The results of firing any held events
     * @since   1.0
     * @version 1.0
     */
    public function subscribe(array $eventHandlers)
    {
        $results = [];

        foreach ($eventHandlers as $eventName => $handler):
            if (!self::isValidHandler($handler)):
                throw new \BadMethodCallException('Mediator::subscribe() - invalid handler passed for ' . $eventName);
            endif;

            list($eventName, $interval) = $this->extractIntervalFrom($eventName); // milliseconds
            $this->scaffoldIfNotExist($eventName);

            $priority = (isset($handler[1])) ? $handler[1] : self::PRIORITY_NORMAL;

            $this->subscribers[$eventName][$priority][] = self::subscriberFromHandler($handler, $interval);
            $this->subscribers[$eventName]['subscribers']++;

            // there will never be held timer events, but otherwise fire matching held events
            if ($interval === 0):
                $results[] = $this->fireHeldEvents($eventName, $priority);
            endif;
        endforeach;

        return $results;
    }

    /**
     * Let any relevant subscribers know an event needs to be handled
     *
     * Note: The event object can be used to share information to other similar event handlers.
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
        // all is greedy, any is not - due to order
        foreach (['all', $event->name, 'any'] as $e):
            if ($this->hasSubscribers($e)):
                $eventNames[] = $e;
            endif;
        endforeach;

        // If no subscribers are listening to this event, try holding it
        if (empty($eventNames)):
            $this->tryHolding($event);
            return;
        endif;

        $result = null;

        foreach ($eventNames as $eventName):

            $sublevels = $this->subscribers[$eventName];
            unset($sublevels['subscribers']);

            if ($priority !== null):
                // If a priority was passed, trim out all others
                $sublevels = [$priority => $sublevels[$priority]];
            endif;

            // Loop through all the subscriber priority levels
            foreach ($sublevels as $plevel => $subs):

                // Loop through the subscribers of this priority level
                foreach ($subs as $i => $subscriber):

                    // If the event's cancelled and the subscriber isn't forced, skip it
                    if ($event->cancelled && !$subscriber['force']):
                        continue;
                    endif;

                    // If the subscriber is a timer...
                    if ($subscriber['interval'] !== 0):
                        // Then if the current time is before when the sub needs to be called
                        if (self::currentTimeMillis() < $subscriber['nextcalltime']):
                            // It's not time yet, so skip it
                            continue;
                        endif;

                        // Mark down the next call time as another interval away
                        $this->subscribers[$eventName][$plevel][$i]['nextcalltime']
                            += $subscriber['interval'];
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
                // handle an array of subscribers recursively if that's what we're given
                // or else we're unsubscribing all from $eventName's events
                $this->unsubscribe($event, (is_array($subscriber)) ? $subscriber[0] : null);
            endforeach;
            return $this;
        endif;

        if ($callback === null):
            // we're unsubscribing all of $eventName
            unset($this->subscribers[$eventName]);
            return $this;
        endif;

        if (is_object($callback) && $callback instanceof Observer):
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

        // If the event has not been subscribed to by this callback then return
        if (($priority = $this->isSubscribed($eventName, $callback)) === false):
            return $this;
        endif;

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

        return $this;
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
     * Get or set the value of the holdingUnheardEvents property
     *
     * @api
     * @param   bool|null   $val    true or false to set the value, omit to retrieve
     * @return  bool    the value of the property
     * @since   1.0
     * @version 1.0
     */
    public function holdUnheardEvents($val = null)
    {
        if ($val === null):
            return $this->holdingUnheardEvents;
        endif;

        $val = (bool) $val;
        if ($val === false):
            $this->held = []; // make sure the held list is wiped clean
        endif;
        return ($this->holdingUnheardEvents = $val);
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
     *
     */
    protected function extractIntervalFrom($eventName)
    {
        $interval = (strpos($eventName, 'timer:') === 0)
            ? (int) substr($eventName, 6)
            : 0;

        return [($interval !== 0) ? 'timer' : $eventName, $interval];
    }

    /**
     * If any events are held for $eventName, re-publish them now
     *
     * @internal
     * @param   string  $eventName  The event name to check for
     * @param   integer $priority   The priority to assign to published events
     * @since   1.0
     * @version 1.0
     */
    protected function fireHeldEvents($eventName, $priority)
    {
        $results = [];
        // loop through any held events
        foreach ($this->held as $i => $e):
            // if this held event's name matches our new subscriber
            if ($e->getName() == $eventName):
                // re-publish that matching held event
                $results[] = $this->publish(array_splice($this->held, $i, 1)[0], $priority);
            endif;
        endforeach;
        return $results;
    }

    /**
     *
     */
    protected function scaffoldIfNotExist($eventName)
    {
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
    }

    /**
     * Puts an event on the held list if enabled and not a timer
     *
     * @internal
     * @param   Event   $event   The event object to be held
     * @since   1.0
     * @version 1.0
     */
    protected function tryHolding(Event $event)
    {
        if ($this->holdingUnheardEvents && $event->name != 'timer'):
            array_unshift($this->held, $event);
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
    protected static function arraySearchDeep($needle, array $haystack)
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
     *
     */
    protected static function subscriberFromHandler($handler, $interval = 0)
    {
        return [
            'callback' => $handler[0],
            'force'    => (isset($handler[2])) ? $handler[2] : false,
            'interval' => $interval,
            'nextcalltime' => self::currentTimeMillis() + $interval,
        ];
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
