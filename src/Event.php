<?php

namespace Noair;

/**
 * Event Class
 *
 * Objects of this class will be passed, whenever an event is fired, to all
 * handlers of said event along with their results. This class also allows
 * event handlers to easily share information with other event handlers.
 *
 * @author  Garrett Whitehorn
 * @author  David Tkachuk
 * @package Noair
 * @version 1.0
 */
class Event
{
    /**
     * The name of the event
     *
     * @access  private
     * @since   1.0
     */
    private $name;

    /**
     * Who fired this event
     *
     * @access  private
     * @since   1.0
     */
    private $caller;

    /**
     * A boolean that indicates if the event is cancelled
     *
     * @access  private
     * @since   1.0
     */
    private $cancelled = false;

    /**
     * An array containing the event's data
     *
     * @access  private
     * @since   1.0
     */
    private $data = [];

    /**
     * An instance of the main Noair class
     *
     * @access  private
     * @since   1.0
     */
    private $noair = null;

    /**
     * An array that contains the results of previous event handlers
     *
     * @access  private
     * @since   1.0
     */
    private $previousResults = [];

    /**
     * Constructor method of Event
     *
     * All of these properties' usage details are left up to the event handler,
     * so see your event handler to know what to pass here.
     *
     * @access  public
     * @param   string  $name   The name of the event
     * @param   mixed   $caller The calling object or class name (optional)
     * @param   mixed   $data   Data to be used by the event's handler (optional)
     * @param   \Noair\Noair  $noair A reference back to a Noair instance (optional)
     * @since   1.0
     */
    public function __construct($name, $data = null, $caller = null)
    {
        $this->name     = $name;
        $this->data     = $data;
        $this->caller   = $caller;
    }

    /**
     * Returns the event's name
     *
     * @access  public
     * @return  string  Event name
     * @since   1.0
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the event's data
     *
     * @access  public
     * @param   mixed   $key    An array key (optional)
     * @return  mixed   The entire data array if no params, otherwise a specific key
     * @since   1.0
     */
    public function getData($key = null)
    {
        if ($key === null):
            return $this->data;
        endif;
        if (isset($this->data[$key])):
            return $this->data[$key];
        endif;
    }

    /**
     * Returns the event's calling object or class name
     *
     * @access  public
     * @return  mixed  Calling object or class name
     * @since   1.0
     */
    public function getCaller()
    {
        return $this->caller;
    }

    /**
     * Returns our Noair instance
     *
     * @access  public
     * @return  \Noair\Noair  Noair object reference
     * @since   1.0
     */
    public function getNoair()
    {
        return $this->noair;
    }

    /**
     * Gets an array of all previous event handlers' results
     *
     * @access  public
     * @return  array   Array of previous event handlers results
     * @since   1.0
     */
    public function getPreviousResults()
    {
        return $this->previousResults;
    }

    /**
     * Gets the result of the previous event handler
     *
     * @access  public
     * @return  mixed   Result of previous event handler
     * @since   1.0
     */
    public function getPreviousResult()
    {
        return $this->previousResults[count($this->previousResults)-1];
    }

    /**
     * Adds the previous event handler's result
     *
     * @access  public
     * @param   mixed   $result The result of the previous event handler
     * @since   1.0
     */
    public function addPreviousResult($result)
    {
        $this->previousResults[] = $result;
        return $result;
    }

    /**
     * Sets our reference to the Noair instance using us
     *
     * Called automatically by Noair->publish().
     *
     * @access  public
     * @param   mixed   $result The result of the previous event handler
     * @since   1.0
     */
    public function setNoair(Noair $noair)
    {
        $this->noair = $noair;
    }

    /**
     * Determine whether further subscriber calls for this event will be stopped
     *
     * @access  public
     * @param   bool    $cancel Cancel the event or not
     * @return  bool    Returns the new value we've set it to
     * @since   1.0
     */
    public function setCancelled($cancel = true)
    {
        return ($this->cancelled = (bool) $cancel);
    }

    /**
     * Return whether the event is cancelled
     *
     * @access  public
     * @return  bool    True if event is cancelled, otherwise false
     * @since   1.0
     */
    public function isCancelled()
    {
        return $this->cancelled;
    }
}
