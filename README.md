[![Total Downloads](https://poser.pugx.org/garrettw/noair/downloads.svg)](https://packagist.org/packages/garrettw/noair) [![Latest Stable Version](https://poser.pugx.org/garrettw/noair/v/stable.svg)](https://packagist.org/packages/garrettw/noair) [![Latest Unstable Version](https://poser.pugx.org/garrettw/noair/v/unstable.svg)](https://packagist.org/packages/garrettw/noair) [![License](https://poser.pugx.org/garrettw/noair/license.svg)](https://packagist.org/packages/garrettw/noair)

[![Code Climate](https://codeclimate.com/github/garrettw/noair/badges/gpa.svg)](https://codeclimate.com/github/garrettw/noair) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/fc0bc904-ef77-4ed4-b474-8ce3db9a4cc2/mini.png)](https://insight.sensiolabs.com/projects/fc0bc904-ef77-4ed4-b474-8ce3db9a4cc2)

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

Basic usage
-------
- Your code will involve creation of a Noair Mediator object; this represents a single event hub.
```php
$hub = new \Noair\Mediator();
```
- Then, you can create objects of your own "listener" classes.
```php
class MyListener extends \Noair\AbstractObserver
{
    public function onThing(\Noair\Event $e)
    {
        return 'do it ' . $e->data;
    }
}

$ear = new MyListener($hub);
```
- Now, your new listener object will need to subscribe() to a specific Noair instance.
```php
$ear->subscribe();
// You could also combine the two previous lines like so:
// $ear = (new MyListener($hub))->subscribe();
```
- You will then use that Noair object in code to publish events that the "listener" classes may handle.
```php
$hub->publish(new \Noair\Event('thing', 'now'));

// Now if you're an object-oriented fiend like me, you'll probably be calling that
// from within a method, like so:
// $this->mediator->publish(new \Noair\Event('thing', 'now', $this));

// Anyway, either of those will return: 'do it now'
```

Core Principles
-------
- Observers are objects with methods that are called by fired events.
- Those methods are called handlers, or once they are registered, subscribers.
- It is recommended (but optional) that observer objects be used to contain handlers.
