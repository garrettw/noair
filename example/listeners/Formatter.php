<?php

use Noair\Listener,
    Noair\Event;

/**
 * A default Noair listener
 *
 * This is the default Noair listener, which other plugins/listeners
 * will override its functionality
 *
 * @author      Garrett Whitehorn
 * @author      David Tkachuk
 * @package     Noair
 * @subpackage  NoairExample
 * @version     1.0
 */
class Formatter extends Listener
{
    public function __construct() {
        // This is just here for an example of explicitly-defined handlers
        $this->handlers = [
            ['formatUsername', [$this, 'formatUsername']],
            ['formatGroup',    [$this, 'formatGroup']],
            ['formatDate',     [$this, 'formatDate']],
            ['formatMessage',  [$this, 'formatMessage']],
        ];
    }

    public function formatUsername(Event $event) {
        return $event->getData();
    }

    public function formatGroup(Event $event) {
        return $event->getData();
    }

    public function formatMessage(Event $event) {
        return nl2br($event->getData());
    }

    public function formatDate(Event $event) {
        return date('F j, Y h:i:s A', $event->getData());
    }

    public function onCreatePost(Event $event) {
        $result = '<div style="padding: 9px 16px;border:1px solid #EEE;margin-bottom:16px;">'
                 .'<strong>Posted by</strong> '
                 .$this->noair->publish(new Event('formatUsername', $event->getData('username'), $this))
                 .' ('
                 .$this->noair->publish(new Event('formatGroup', $event->getData('group'), $this))
                 .')<br /><strong>Posted Date</strong> '
                 .$this->noair->publish(new Event('formatDate', $event->getData('date'), $this))
                 .'<br />'
                 .$this->noair->publish(new Event('formatMessage', $event->getData('message'), $this))
                 .'</div>';

        return $result;
    }
}
