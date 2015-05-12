<?php

namespace Noair;

interface Observable
{
    public function subscribe($eventName, callable $callback, &$results,
        $priority, $force
    );
    public function publish(Event $event, $priority);
    public function unsubscribe($eventName, $callback);
}
