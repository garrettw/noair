[![Total Downloads](https://poser.pugx.org/garrettw/noair/downloads.svg)](https://packagist.org/packages/garrettw/noair) [![Latest Stable Version](https://poser.pugx.org/garrettw/noair/v/stable.svg)](https://packagist.org/packages/garrettw/noair) [![Latest Unstable Version](https://poser.pugx.org/garrettw/noair/v/unstable.svg)](https://packagist.org/packages/garrettw/noair) [![License](https://poser.pugx.org/garrettw/noair/license.svg)](https://packagist.org/packages/garrettw/noair)

[![Code Climate](https://codeclimate.com/github/garrettw/noair/badges/gpa.svg)](https://codeclimate.com/github/garrettw/noair) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/fc0bc904-ef77-4ed4-b474-8ce3db9a4cc2/mini.png)](https://insight.sensiolabs.com/projects/fc0bc904-ef77-4ed4-b474-8ce3db9a4cc2) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/garrettw/noair/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/garrettw/noair/?branch=master)

Noair
======

Noair (pronounced "no-air") is a PHP library that provides a central event hub
that other things can hook into to handle events that are published into that hub.
It implements the Mediator behavioral pattern in an Observer style.

**"Why is it called that?"**

We'll get to that in a minute.

**"Why should I use Noair instead of all the other event libraries out there?"**

Because Noair:
- Uses standard Observer-pattern terminology (publish/subscribe)
- Can optionally hang onto published events for which there is no subscriber until such time as a handler subscribes to it
- Supports timer events in addition to normal published events
- Allows handlers to subscribe to any and all events if they want
- Encapsulates event information in an object that gets passed to handlers
- Event objects can hold custom data set by the publisher for handlers to use
- Event objects allow handlers to access the objects that published them
- Event objects allow handlers to access previous handler output (for daisy-chaining)
- Event objects can prevent further daisy-chaining by calling setCancelled()
- Handlers can be simple anonymous functions or contained in observer objects; anything callable
- Observer objects can define handlers explicitly or use on*() method naming, where * is the capitalized event name

**"I really need to know the meaning of the name."**

Fine, fine. My project was forked from one called "Podiya", which is Ukrainian for
"event". I found out what the word "Podiya" looked like in its original script,
and I thought it looked like the letters n-o-A-i-R.
[See for yourself.](https://translate.google.com/#en/uk/event)

Core Principles
-------
- Observers are objects with methods that are called by fired events.
- Those methods are called handlers, or once they are registered, subscribers.
- It is recommended (but optional) that observer objects be used to contain handlers.

Basic usage
-------
The idea is that you have multiple event handlers watching a single event hub, waiting for events they can handle.

- First, you'll create child classes of Noair's abstract Observer class that contain handlers:
```php
class MyObserver extends \Noair\Observer
{
    public function onThing(\Noair\Event $e)
    {
        return 'do it ' . $e->data;
    }
}
```
- Now, in the main code you're executing, you'll need to create a hub for your events: a Mediator object which serves as a go-between for your handlers and your code that fires/publishes the events.
```php
$hub = new \Noair\Mediator();
```
- Then, you can create objects of your own Observer classes and subscribe them to the hub.
```php
$obs = (new MyObserver($hub))->subscribe();
```
- You will then use that Mediator object in your code to publish events that the Observer classes may handle.
```php
$hub->publish(new \Noair\Event('thing', 'now'));

// Now if you're an object-oriented fiend like me, you'll probably be calling that
// from within a method, like so:
// $this->mediator->publish(new \Noair\Event('thing', 'now', $this));

// Anyway, either of those will return: 'do it now'
```

Advanced usage
-------
The only "advanced" thing you can do is set up handlers with custom method names,
custom priorities, or forceability (this means that the handler will be run even if
another handler higher up the chain tries to cancel the rest of the chain).
You do this by defining (actually, overriding) the `subscribe()` method as follows:

```php
class OtherObserver extends \Noair\Observer
{
    public function subscribe() {
        $this->handlers = [
            'doWeirdThings' => [ // an event name that this class handles
                [$this, 'doWeirdThingsAlways'], // the callable that the event fires
                \Noair\Mediator::PRIORITY_HIGHEST, // how important this handler is
                true, // this is the forceability setting
            ],
        ];

        return parent::subscribe();
    }

    // This is just a normal handler
    public function onThing(\Noair\Event $e)
    {
        return 'do it ' . $e->data;
    }

    // Wait, this function doesn't start with "on"! How can it work?
    // See subscribe() above.
    public function doWeirdThingsAlways(\Noair\Event $e)
    {
        return 'do ' . $e->data . ' ' . rand() . ' times';
    }
}

$hub = new \Noair\Mediator();
$obs = (new OtherObserver($hub))->subscribe();

$hub->publish(new \Noair\Event('doWeirdThings', 'stuff'));
```

That might return: `do stuff 5623 times`
