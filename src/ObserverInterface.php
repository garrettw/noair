<?php

namespace Noair;

interface ObserverInterface
{
    /**
     * @return  self    This observer object
     */
    public function subscribe();

    /**
     * @return  self    This observer object
     */
    public function unsubscribe();
}
