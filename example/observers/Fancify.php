<?php

use Noair\AbstractObserver,
    Noair\Event;

/**
 * An example Noair Observer
 *
 * This is an example Observer/plugin, which will modify
 * previously called listeners. This example Observer enhances
 * the display of posts
 *
 * @author      Garrett Whitehorn
 * @author      David Tkachuk
 * @package     Noair
 * @subpackage  NoairExample
 * @version     1.0
 */
class Fancify extends AbstractObserver
{
    public function onCreatePost(Event $event) {
        return str_replace('border:1px solid #EEE;',
            'border:1px solid #DADADA;background:#F1F1F1;font-family:Arial;font-size:15px;',
            $event->previousResult);
    }
}
