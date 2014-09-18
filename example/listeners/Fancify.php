<?php

use Noair\Noair,
    Noair\Listener,
    Noair\Event;

/**
 * An example Podiya listener
 *
 * This is an example listener/plugin, which will modify
 * previously called listeners. This example listener enhances
 * the display of posts
 *
 * @author      David Tkachuk
 * @package     Noair
 * @subpackage  NoairExample
 * @version     1.0
 */
class Fancify extends Listener
{
    public function __construct() {
        $this->handlers = [['create_post', [$this, 'fancyPost']]];
    }

    public function fancyPost(Event $event) {
        return str_replace('border:1px solid #EEE;',
            'border:1px solid #DADADA;background:#F1F1F1;font-family:Arial;font-size:15px;',
            $event->getPreviousResult());
    }
}
