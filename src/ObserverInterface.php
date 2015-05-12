<?php

namespace Noair;

interface ObserverInterface
{
    public function subscribe();
    public function unsubscribe();
}
