<?php

namespace Noair;

/**
 * Event Class
 *
 * Objects of this class will be passed, whenever an event is fired, to all
 * handlers of said event along with their results. This class also allows
 * event handlers to easily share information with other event handlers.
 *
 * Extend this class if you want to impose some sort of structure on the data
 * contained in your specific event type. You could validate the $data array or
 * add custom properties.
 *
 * @author  Garrett Whitehorn
 * @author  David Tkachuk
 * @package Noair
 * @version 1.0
 */
class Event
{
    /**
     * @api
     * @var     string  The name of the event
     * @since   1.0
     */
    private $name;

    /**
     * @api
     * @var     mixed   Contains the event's data
     * @since   1.0
     */
    private $data;

    /**
     * @api
     * @var     mixed   Who fired this event
     * @since   1.0
     */
    private $caller;

    /**
     * @api
     * @var     bool    Indicates if the event is cancelled
     * @since   1.0
     */
    private $cancelled = false;

    /**
     * @api
     * @var     Noair|null An instance of the main Noair class
     * @since   1.0
     */
    private $noair = null;

    /**
     * @api
     * @var     array   Contains the results of previous event handlers
     * @since   1.0
     */
    private $previousResults = [];

    /**
     * Constructor method of Event
     *
     * All of these properties' usage details are left up to the event handler,
     * so see your event handler to know what to pass here.
     *
     * @api
     * @param   string  $name   The name of the event
     * @param   mixed   $data   Data to be used by the event's handler (optional)
     * @param   mixed   $caller The calling object or class name (optional)
     * @since   1.0
     * @version 1.0
     */
    public function __construct($name, $data = null, $caller = null)
    {
        $this->name     = $name;
        $this->data     = $data;
        $this->caller   = $caller;
    }

    public function __get($name)
    {
        if ($name == 'previousResult'):
            return $this->previousResults[count($this->previousResults)-1];
        endif;

        return $this->$name;
    }

    public function __set($name, $val)
    {
        if ($name == 'previousResult'):
            $this->previousResults[] = $val;
        elseif ($name == 'cancelled'):
            $this->cancelled = (bool) $val;
        elseif ($name == 'noair' && $val instanceof Noair || $val === null):
            $this->noair = $val;
        else:
            $this->$name = $val;
        endif;
    }
}
