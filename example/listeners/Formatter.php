<?php

use Noair\Noair,
    Noair\Listener,
    Noair\Event;

/**
 * A default Podiya listener
 *
 * This is the default Podiya listener, which other plugins/listeners
 * will override its functionality
 *
 * @author      David Tkachuk
 * @package     Noair
 * @subpackage  NoairExample
 * @version     1.0
 */
class Formatter extends Listener
{
    public function __construct() {
        // events we will handle
        $this->handlers = [
            ['format_username', [$this, 'formatUsername']],
            ['format_group',    [$this, 'formatGroup']],
            ['format_date',     [$this, 'formatDate']],
            ['format_message',  [$this, 'formatMessage']],
            ['create_post',     [$this, 'makePost']],
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

    public function makePost(Event $event) {
        $result = '<div style="padding: 9px 16px;border:1px solid #EEE;margin-bottom:16px;">'
                 .'<strong>Posted by</strong> '
                 .$this->noair->publish(new Event('format_username', $event->getData('username'), $this))
                 .' ('
                 .$this->noair->publish(new Event('format_group', $event->getData('group'), $this))
                 .')<br /><strong>Posted Date</strong> '
                 .$this->noair->publish(new Event('format_date', $event->getData('date'), $this))
                 .'<br />'
                 .$this->noair->publish(new Event('format_message', $event->getData('message'), $this))
                 .'</div>';

        return $result;
    }
}
