<?php

use Noair\Listener,
    Noair\Event;

/**
 * An example Noair listener
 *
 * This is an example listener/plugin, which will override
 * previously called listeners. This example listener enhances
 * the group and date formatting
 *
 * @author      Garrett Whitehorn
 * @author      David Tkachuk
 * @package     Noair
 * @subpackage  NoairExample
 * @version     1.0
 */
class BetterFormatter extends Listener
{
    public function onFormatGroup(Event $event) {
        $groupName = strtolower($event->getData());
        switch ($groupName) {
            case 'admin':
            case 'administrator':
                $groupName = '<span style="color:#F00;">Administrator</span>';
                break;

            case 'mod':
            case 'moderator':
                $groupName = '<span style="color:#00A;">Moderator</span>';
                break;
        }
        return $groupName;
    }

    public function onFormatDate(Event $event) {
        return date('F j, Y h:i:s A T', $event->getData());
    }
}
