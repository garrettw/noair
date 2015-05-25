<?php

namespace Noair;

interface Observable
{
    /**
     * @return  self  This object
     */
    public function subscribe($eventName, callable $callback, $priority, $force);

    /**
     * @return  mixed   Result of the event
     */
    public function publish(Event $event, $priority);

    /**
     * @return  self  This object
     */
    public function unsubscribe($eventName, $callback);
}
