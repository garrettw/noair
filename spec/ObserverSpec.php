<?php

namespace spec\Noair;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ObserverSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beAnInstanceOf('spec\Noair\ObserverExample');
        $this->beConstructedWith(new \Noair\Mediator());
        $this->subscribe();
    }

    function it_is_initializable()
    {
        $this->shouldBeAnInstanceOf('Noair\Observer');
        $this->shouldBeAnInstanceOf('spec\Noair\ObserverExample');
    }

    function it_keeps_an_instance_of_mediator()
    {
        $this->mediator->shouldBeAnInstanceOf('Noair\Mediator');
    }

    function it_responds_to_all_events()
    {
        $this->mediator->publish(new \Noair\Event('random'))->shouldReturn('random');
    }
}

class ObserverExample extends \Noair\Observer
{
    public function subscribe() {
        // This is just here for an example of explicitly-defined handlers
        $this->handlers = [
            'all' => [[$this, 'log'], \Noair\Mediator::PRIORITY_URGENT, true],
            'one' => [[$this, 'handlerOne']],
        ];
        parent::subscribe();
    }

    public function handlerOne(\Noair\Event $e)
    {
        return 'one';
    }

    public function onTwo(\Noair\Event $e)
    {
        return 'two';
    }

    public function log(\Noair\Event $e)
    {
        return $e->name;
    }
}
