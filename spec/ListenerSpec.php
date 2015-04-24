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
    }
}

class ListenerExample extends \Noair\Listener
{

}
