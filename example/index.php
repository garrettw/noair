<?php

define('BASEDIR', dirname(__FILE__));
define('SRCDIR', dirname(BASEDIR) . '/src');

// Include Noair classes
include SRCDIR . '/Observable.php';
include SRCDIR . '/Mediator.php';
include SRCDIR . '/Event.php';
include SRCDIR . '/ObserverInterface.php';
include SRCDIR . '/Observer.php';
use Noair\Mediator,
    Noair\Event;

// Setup Noair
$hub = new Mediator();

// Include the observers
include BASEDIR . '/observers/Formatter.php';
include BASEDIR . '/observers/FancyExamplePlugin.php';
include BASEDIR . '/observers/BetterFormatter.php';
include BASEDIR . '/observers/Fancify.php';

// Initialize the default application observers
$formatter = (new Formatter($hub))->subscribe();
// Initialize plugin observers--assigned to vars so we can mess with them later
$fancyExamplePlugin = (new FancyExamplePlugin($hub))->subscribe();
$betterFormatter    = (new BetterFormatter($hub))->subscribe();
$fancify            = (new Fancify($hub))->subscribe();

$sampleMessage = <<<HTML
Lorem [b]ipsum dolor sit amet[/b], consectetur adipiscing elit. Fusce dignissim neque vitae velit mollis, ac volutpat mauris consequat. Morbi sed arcu leo. Vestibulum dignissim, est at blandit suscipit, sapien leo [u]iaculis massa, mollis faucibus[/u] odio mauris sed risus. Integer mollis, ipsum ut efficitur lobortis, ex enim dictum felis, in mattis purus orci [b]in nulla. Nunc [u]semper mauris[/u] enim[/b], quis faucibus massa luctus quis. Sed ut malesuada magna, cursus ullamcorper augue. Curabitur orci nisl, mattis quis elementum eu, condimentum at lorem. Interdum et malesuada fames ac ante ipsum primis in faucibus. Aliquam ultricies tristique urna in maximus. Praesent facilisis, [url=http://github.com/DavidRockin]diam ac euismod sollicitudin[/url], eros diam consectetur est, quis egestas nisl orci vel nisl. Aenean consectetur justo non felis varius, eu fermentum mi fermentum. Ut ac dui ligula.
For more information please visit [url]http://github.com/DavidRockin[/url]
HTML;


echo "With better formatting\n",
    $hub->publish(new Event('createPost', [
        'username' => 'David',
        'group'    => 'Administrator',
        'date'     => time(),
        'message'  => $sampleMessage,
    ])), "\n",
    $hub->publish(new Event('createPost', [
        'username' => 'John Doe',
        'group'    => 'Moderator',
        'date'     => strtotime('-3 days'),
        'message'  => $sampleMessage,
    ]));

// Usually this should be handled by custom public methods in the observers,
// because this code wouldn't be aware of the exact subscription
$hub->unsubscribe('formatGroup', $betterFormatter);

$fancify->unsubscribe();

echo "\n\nWithout the better formatting on group and post\n",
    $hub->publish(new Event('createPost', [
        'username' => 'AppleJuice',
        'group'    => 'Member',
        'date'     => strtotime('-3 weeks'),
        'message'  => $sampleMessage,
    ])), "\n",
    $hub->publish(new Event('createPost', [
        'username' => 'Anonymous',
        'group'    => 'Donator',
        'date'     => strtotime('-3 years'),
        'message'  => $sampleMessage,
    ]));

$fancyExamplePlugin->unsubscribe();

echo "\n\nAfter destroying the fancyExamplePlugin observer\n",
    $hub->publish(new Event('createPost', [
        'username' => 'AppleJuice',
        'group'    => 'Member',
        'date'     => strtotime('-3 weeks'),
        'message'  => $sampleMessage,
    ]));
