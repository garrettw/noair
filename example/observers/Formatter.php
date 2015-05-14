<?php

use Noair\AbstractObserver,
    Noair\Event;

/**
 * A default Noair Observer
 *
 * This is the default Noair Observer, which other plugins/listeners
 * will override its functionality
 *
 * @author      Garrett Whitehorn
 * @author      David Tkachuk
 * @package     Noair
 * @subpackage  NoairExample
 * @version     1.0
 */
class Formatter extends AbstractObserver
{
    public function subscribe() {
        // This is just here for an example of explicitly-defined handlers
        $this->handlers = [
            'formatUsername' => [[$this, 'formatUsername']],
            'formatGroup'    => [[$this, 'formatGroup']],
            'formatDate'     => [[$this, 'formatDate']],
            'formatMessage'  => [[$this, 'formatMessage']],
        ];

        return parent::subscribe();
    }

    public function formatUsername(Event $event) {
        return $event->data;
    }

    public function formatGroup(Event $event) {
        return $event->data;
    }

    public function formatMessage(Event $event) {
        return nl2br($event->data);
    }

    public function formatDate(Event $event) {
        return date('F j, Y h:i:s A', $event->data);
    }

    public function onCreatePost(Event $event) {
        $result = '<div style="padding: 9px 16px;border:1px solid #EEE;margin-bottom:16px;">'
                 .'<strong>Posted by</strong> '
                 .$this->mediator->publish(new Event('formatUsername', $event->data['username'], $this))
                 .' ('
                 .$this->mediator->publish(new Event('formatGroup', $event->data['group'], $this))
                 .')<br /><strong>Posted Date</strong> '
                 .$this->mediator->publish(new Event('formatDate', $event->data['date'], $this))
                 .'<br />'
                 .$this->mediator->publish(new Event('formatMessage', $event->data['message'], $this))
                 .'</div>';
        return $result;
    }
}
