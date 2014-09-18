<?php

namespace Noair;

/**
 * Noair listener class -- to be extended
 *
 * @author  Garrett Whitehorn
 * @package Noair
 * @version 1.0
 */
abstract class Listener
{
    /**
     * The array of event handlers we'll be subscribing.
     *
     * This can be set by child class constructors so that Noair can call
     * getEvents() and register the results. If this array is empty when the
     * listener is to be registered, Noair will analyze any on* method names and
     * register them automagically. However, doing it that way means you forfeit
     * the ability to give handlers priority and forceability.
     *
     * Terminology note: they're not subscribers until they're subscribed ;)
     *
     * @access  protected
     * @since   1.0
     */
    protected $handlers = [];

    /**
     * Our instance of Noair that our handlers are registered with
     *
     * @access  protected
     * @since   1.0
     */
    protected $noair;

    /**
     * Registers our handlers with a particular Noair instance
     *
     * @access  public
     * @param   Noair   $noair  The Noair instance we'll be using
     * @return  void
     * @since   1.0
     */
    public function listenTo(Noair $noair)
    {
        if (!(isset($this->handlers) && is_array($this->handlers) && count($this->handlers))) {
            throw new \RuntimeException('$this->handlers is empty or not set');
        }

        $this->noair = $noair;
        $this->noair->subscribe($this->handlers);
        return $this;
    }

    /**
     * Registers our handlers
     *
     * @access  public
     * @param   Noair   $noair  The Noair instance we'll be using
     * @return  void
     * @since   1.0
     */
    public function unlisten()
    {
        if (isset($this->noair)) {
            $this->noair->unsubscribe($this->handlers);
            unset($this->noair);
        }
        return $this;
    }
}
