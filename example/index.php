<?php

define('BASEDIR', dirname(__FILE__));
define('SRCDIR', dirname(BASEDIR) . '/src');

// Include Noair classes
include SRCDIR . '/Noair.php';
include SRCDIR . '/Event.php';
include SRCDIR . '/Listener.php';
use Noair\Noair,
    Noair\Event;

// Setup Noair
$noair = new Noair();

// Include the listeners
include BASEDIR . '/listeners/Formatter.php';
include BASEDIR . '/listeners/FancyExamplePlugin.php';
include BASEDIR . '/listeners/BetterFormatter.php';
include BASEDIR . '/listeners/Fancify.php';

// Initialize the default application listeners
$formatter = (new Formatter())->listenTo($noair);
// Initialize plugin listeners--assigned to vars so we can mess with them later
$fancyExamplePlugin = (new FancyExamplePlugin())->listenTo($noair);
$betterFormatter    = (new BetterFormatter())->listenTo($noair);
$fancify            = (new Fancify())->listenTo($noair);

$sampleMessage = <<<HTML
Lorem [b]ipsum dolor sit amet[/b], consectetur adipiscing elit. Fusce dignissim neque vitae velit mollis, ac volutpat mauris consequat. Morbi sed arcu leo. Vestibulum dignissim, est at blandit suscipit, sapien leo [u]iaculis massa, mollis faucibus[/u] odio mauris sed risus. Integer mollis, ipsum ut efficitur lobortis, ex enim dictum felis, in mattis purus orci [b]in nulla. Nunc [u]semper mauris[/u] enim[/b], quis faucibus massa luctus quis. Sed ut malesuada magna, cursus ullamcorper augue. Curabitur orci nisl, mattis quis elementum eu, condimentum at lorem. Interdum et malesuada fames ac ante ipsum primis in faucibus. Aliquam ultricies tristique urna in maximus. Praesent facilisis, [url=http://github.com/DavidRockin]diam ac euismod sollicitudin[/url], eros diam consectetur est, quis egestas nisl orci vel nisl. Aenean consectetur justo non felis varius, eu fermentum mi fermentum. Ut ac dui ligula.
For more information please visit [url]http://github.com/DavidRockin[/url]
HTML;


echo "With better formatting\n",
    $noair->publish(new Event('create_post', [
        'username' => 'David',
        'group'    => 'Administrator',
        'date'     => time(),
        'message'  => $sampleMessage,
    ])), "\n",
    $noair->publish(new Event('create_post', [
        'username' => 'John Doe',
        'group'    => 'Moderator',
        'date'     => strtotime('-3 days'),
        'message'  => $sampleMessage,
    ]));

// Usually this should be handled by custom methods in the listeners,
// because this code wouldn't be aware of the exact subscription
$noair->unsubscribe('format_group', [$betterFormatter, 'betterGroup']);

$fancify->unlisten();

echo "\n\nWithout the better formatting on group and post\n",
    $noair->publish(new Event('create_post', [
        'username' => 'AppleJuice',
        'group'    => 'Member',
        'date'     => strtotime('-3 weeks'),
        'message'  => $sampleMessage,
    ])), "\n",
    $noair->publish(new Event('create_post', [
        'username' => 'Anonymous',
        'group'    => 'Donator',
        'date'     => strtotime('-3 years'),
        'message'  => $sampleMessage,
    ]));

$fancyExamplePlugin->unlisten();

echo "\n\nAfter destroying the fancyExamplePlugin listener\n",
    $noair->publish(new Event('create_post', [
        'username' => 'AppleJuice',
        'group'    => 'Member',
        'date'     => strtotime('-3 weeks'),
        'message'  => $sampleMessage,
    ]));
