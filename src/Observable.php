<?php

namespace Noair;

interface Observable
{
    /**
     * Registers event handler(s) to event name(s)
     *
     * @api
     * @param   array   $eventHandlers  Associative array of event names & handlers
     * @return  array   The results of firing any held events
     * @since   1.0
     * @version 1.0
     */
    public function subscribe(array $eventHandlers);

    /**
     * Let any relevant subscribers know an event needs to be handled
     *
     * Note: The event object can be used to share information to other similar
     * event handlers.
     *
     * @api
     * @param   Event   $event  An event object, usually freshly created
     * @return  mixed   Result of the event
     * @since   1.0
     * @version 1.0
     */
    public function publish(Event $event);

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
    public function unsubscribe($eventName, $callback = null);
}
