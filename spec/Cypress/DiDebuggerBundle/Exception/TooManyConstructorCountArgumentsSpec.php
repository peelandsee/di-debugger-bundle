<?php

namespace spec\Cypress\DiDebuggerBundle\Exception;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TooManyConstructorCountArgumentsSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Cypress\DiDebuggerBundle\Exception\TooManyConstructorCountArguments');
    }

    function it_extends_exception()
    {
        $this->shouldbeAnInstanceOf('\Exception');
        $this->shouldbeAnInstanceOf('Cypress\DiDebuggerBundle\Exception\WrongConstructorCountArguments');
    }
}
