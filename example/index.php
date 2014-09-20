<?php

define('BASEDIR', dirname(__FILE__));
define('SRCDIR', dirname(BASEDIR) . '/src');

// Include Podiya classes
include SRCDIR . '/Podiya.php';
include SRCDIR . '/Event.php';
include SRCDIR . '/Listener.php';
use DavidRockin\Podiya\Podiya,
    DavidRockin\Podiya\Event;

// Setup Podiya
$podiya = new Podiya;

// Include the listeners
include BASEDIR . '/Application/Formatter.php';
include BASEDIR . '/DavidRockin/FancyExamplePlugin.php';
include BASEDIR . '/DavidRockin/BetterFormatter.php';
include BASEDIR . '/DavidRockin/Fancify.php';

// Initialize the default application listeners
$defaultFormatter = new \DavidRockin\PodiyaExample\Formatter($podiya);
// Initialize plugin listeners
$fancyExamplePlugin = new \DavidRockin\PodiyaExample\FancyExamplePlugin($podiya);
$betterFormatter    = new \DavidRockin\PodiyaExample\BetterFormatter($podiya);
$fancify            = new \DavidRockin\PodiyaExample\Fancify($podiya);

$sampleMessage = <<<HTML
Lorem [b]ipsum dolor sit amet[/b], consectetur adipiscing elit. Fusce dignissim neque vitae velit mollis, ac volutpat mauris consequat. Morbi sed arcu leo. Vestibulum dignissim, est at blandit suscipit, sapien leo [u]iaculis massa, mollis faucibus[/u] odio mauris sed risus. Integer mollis, ipsum ut efficitur lobortis, ex enim dictum felis, in mattis purus orci [b]in nulla. Nunc [u]semper mauris[/u] enim[/b], quis faucibus massa luctus quis. Sed ut malesuada magna, cursus ullamcorper augue. Curabitur orci nisl, mattis quis elementum eu, condimentum at lorem. Interdum et malesuada fames ac ante ipsum primis in faucibus. Aliquam ultricies tristique urna in maximus. Praesent facilisis, [url=http://github.com/DavidRockin]diam ac euismod sollicitudin[/url], eros diam consectetur est, quis egestas nisl orci vel nisl. Aenean consectetur justo non felis varius, eu fermentum mi fermentum. Ut ac dui ligula.
For more information please visit [url]http://github.com/DavidRockin[/url]
HTML;


echo "With better formatting\n",
    $podiya->publish(new Event('create_post', [
        'username' => 'David',
        'group'    => 'Administrator',
        'date'     => time(),
        'message'  => $sampleMessage,
    ])), "\n",
    $podiya->publish(new Event('create_post', [
        'username' => 'John Doe',
        'group'    => 'Moderator',
        'date'     => strtotime('-3 days'),
        'message'  => $sampleMessage,
    ]));

$podiya->unsubscribe('format_group', [$betterFormatter, 'betterGroup']);
$podiya->unsubscribe('create_post', [$fancify, 'fancyPost']);

echo "\n\nWithout the better formatting on group and post\n",
    $podiya->publish(new Event('create_post', [
        'username' => 'AppleJuice',
        'group'    => 'Member',
        'date'     => strtotime('-3 weeks'),
        'message'  => $sampleMessage,
    ])), "\n",
    $podiya->publish(new Event('create_post', [
        'username' => 'Anonymous',
        'group'    => 'Donator',
        'date'     => strtotime('-3 years'),
        'message'  => $sampleMessage,
    ]));

$fancyExamplePlugin->destroy();

echo "\n\nAfter destroying the fancyExamplePlugin listener\n",
    $podiya->publish(new Event('create_post', [
        'username' => 'AppleJuice',
        'group'    => 'Member',
        'date'     => strtotime('-3 weeks'),
        'message'  => $sampleMessage,
    ]));
