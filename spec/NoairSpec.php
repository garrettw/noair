<?php

namespace spec\Noair;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class NoairSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldBeAnInstanceOf('Noair\Noair');
    }

    function it_will_not_hold_unheard_events_by_default()
    {
        $this->holdUnheardEvents->shouldBe(false);
    }

    function it_may_hold_unheard_events()
    {
        $this->beConstructedWith(true);

        $this->holdUnheardEvents->shouldBe(true);
    }

    function it_clears_the_pending_list()
    {
        $this->beConstructedWith(true);

        $this->publish(new \Noair\Event('randomname'));
        $this->holdUnheardEvents = false;

        $this->pending->shouldEqual([]);
    }

    function it_holds_the_pending_event()
    {
        $this->beConstructedWith(true);

        $this->publish(new \Noair\Event('randomname'));

        $this->pending[0]->shouldBeAnInstanceOf('Noair\Event');
        $this->pending[0]->name->shouldEqual('randomname');
    }

    function it_has_no_subscribers()
    {
        $this->shouldNotHaveSubscribers('randomname');
    }

    function it_can_register_basic_handlers()
    {
        $eventname = 'randomname';
        $callback = function() {};

        $this->subscribe($eventname, $callback);

        $this->shouldHaveSubscribers($eventname);
        $this->isSubscribed($eventname, $callback)->shouldBeInteger();
    }

    function it_can_register_time_handlers()
    {
        $eventname = 'timer';
        $callback = function() {};

        $this->subscribe($eventname . ':10', $callback);

        $this->shouldHaveSubscribers($eventname);
        $this->isSubscribed($eventname, $callback)->shouldBeInteger();
    }

    function it_can_unregister_all_handlers()
    {
        $eventname = 'randomname';
        $callback = function() {};
        $this->subscribe($eventname, $callback);
        $this->subscribe($eventname, $callback);

        $this->unsubscribe($eventname);

        $this->shouldNotHaveSubscribers($eventname);
    }

    function it_can_unregister_specific_handlers()
    {
        $eventname = 'randomname';
        $callback1 = function() { return true; };
        $callback2 = function() { return false; };
        $this->subscribe($eventname, $callback1);
        $this->subscribe($eventname, $callback2);

        $this->unsubscribe($eventname, $callback1);

        $this->isSubscribed($eventname, $callback2)->shouldBeInteger();
        $this->isSubscribed($eventname, $callback1)->shouldReturn(false);
    }

    function it_can_publish_events()
    {
        $eventname = 'randomname';
        $callback = function() { return 'event handled'; };
        $this->subscribe($eventname, $callback);

        $this->publish(new \Noair\Event($eventname))->shouldReturn('event handled');
    }

    function it_can_handle_timed_events()
    {
        $eventname = 'timer';
        $callback = function() { return 'timed event handled'; };
        $this->subscribe($eventname . ':100', $callback);
        $timer = new \Noair\Event('timer');

        while (($result = $this->getWrappedObject()->publish($timer)) === null) {}

        if ($result != 'timed event handled') throw new \Exception("bad result: $result");
    }
}
