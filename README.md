Noair
======

Noair (pronounced "no-air") is a PHP library that provides a central event hub
that other things can hook into to handle events that are published into that hub.
It implements the Mediator (publish-subscribe) pattern in an Observer style.
[![Latest Stable Version](https://poser.pugx.org/garrettw/noair/v/stable.svg)](https://packagist.org/packages/garrettw/noair) [![Total Downloads](https://poser.pugx.org/garrettw/noair/downloads.svg)](https://packagist.org/packages/garrettw/noair) [![Latest Unstable Version](https://poser.pugx.org/garrettw/noair/v/unstable.svg)](https://packagist.org/packages/garrettw/noair) [![License](https://poser.pugx.org/garrettw/noair/license.svg)](https://packagist.org/packages/garrettw/noair)

Basic structure
-------
- Your code will involve creation of a Noair object; this represents a single event hub.
- Then, you will create objects of your own "listener" classes.
- Now, your new listener object can listenTo() a specific Noair instance.
- You will then use that Noair object in code to create/fire/publish events that the "listener" classes may handle.

Features
-------
- You can register listeners that subscribe to any and all events in a single Noair instance

Core Principles
-------
- Listeners are objects with methods that are called by fired events.
- Those methods are called handlers, or once they are registered, subscribers.
- It is recommended (but optional) that Listener objects be used to contain handlers.
