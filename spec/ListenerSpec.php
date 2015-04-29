<?php

namespace spec\Noair;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ListenerSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beAnInstanceOf('spec\Noair\ListenerExample');
    }

    function it_is_initializable()
    {
        $this->shouldBeAnInstanceOf('Noair\Listener');
        $this->shouldBeAnInstanceOf('spec\Noair\ListenerExample');
    }

    function it_gets_all_handlers()
    {
        $gh = $this->getHandlers();
        $gh[0][0]->shouldEqual('all');
        $gh[1][0]->shouldEqual('one');
        $gh[2][0]->shouldEqual('two');
    }

    function it_keeps_an_instance_of_noair()
    {
        $this->subscribe(new \Noair\Noair());

        $this->noair->shouldBeAnInstanceOf('Noair\Noair');
    }

    function it_responds_to_all_events()
    {
        $this->subscribe(new \Noair\Noair());

        $this->noair->publish(new \Noair\Event('random'))->shouldReturn('random');
    }
}

class ListenerExample extends \Noair\Listener
{
    public function __construct() {
        // This is just here for an example of explicitly-defined handlers
        $this->handlers = [
            ['all', [$this, 'log'], \Noair\Noair::PRIORITY_URGENT, true],
            ['one', [$this, 'handlerOne']],
        ];
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
