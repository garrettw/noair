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
 * @property    string  $name   The name of the event
 * @property    mixed   $data   Contains the event's data
 * @property    mixed   $caller Who fired this event
 * @property    bool    $cancelled  Indicates if the event is cancelled
 * @property    Mediator|null   $mediator   An instance of the main Mediator class
 * @property    array   $previousResults    Contains the results of previous event handlers
 * @property    mixed   $previousResult Get = last result, set = adds a new result
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
     * @var     Mediator|null An instance of the main Mediator class
     * @since   1.0
     */
    private $mediator = null;

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

    /**
     * @return  mixed
     */
    public function __get($name)
    {
        if ($name == 'previousResult'):
            return end($this->previousResults);
        endif;

        return $this->$name;
    }

    public function __set($name, $val)
    {
        switch ($name):
            case 'previousResult':
                $this->previousResults[] = $val;
                break;

            case 'cancelled':
                $this->cancelled = (bool) $val;
                break;

            case 'mediator':
                if ($val instanceof Observable || $val === null):
                    $this->mediator = $val;
                endif;
                break;

            default:
                $this->$name = $val;
        endswitch;
    }
}
