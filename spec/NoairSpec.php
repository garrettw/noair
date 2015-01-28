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

        $this->pending->shouldBe([]);
    }

    function it_holds_the_pending_event()
    {
        $this->beConstructedWith(true);
        $this->publish(new \Noair\Event('randomname'));

        $this->pending[0]->shouldBeAnInstanceOf('Noair\Event');
        $this->pending[0]->name->shouldBe('randomname');
    }

    function it_has_no_subscribers()
    {
        $this->shouldNotHaveSubscribers('randomname');
    }
}
