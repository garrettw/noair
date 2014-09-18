<?php

use Noair\Noair,
    Noair\Listener,
    Noair\Event;

/**
 * An example Podiya listener
 *
 * This is an example listener/plugin, which will override
 * previously called listeners. This example listener enhances
 * a post's message
 *
 * @author      David Tkachuk
 * @package     Noair
 * @subpackage  NoairExample
 * @version     1.0
 */
class FancyExamplePlugin extends Listener
{
    public function __construct() {
        $this->handlers = [['format_message', [$this, 'formatMessage']]];
    }

    public function formatMessage(Event $event) {
        $message = strip_tags($event->getData());
        $message = preg_replace('/\[b\](.+?)\[\/b\]/is', '<span style="font-weight:bold">$1</span>', $message);
        $message = preg_replace('/\[u\](.+?)\[\/u\]/is', '<span style="text-decoration:underline">$1</span>', $message);
        $message = preg_replace('/\[url=([^\[\]]+)\](.+?)\[\/url\]/is', '<a href="$1">$2</a>', $message);
        $message = preg_replace('/\[url\](.+?)\[\/url\]/is', '<a href="$1">$1</a>', $message);
        return nl2br($message);
    }
}
