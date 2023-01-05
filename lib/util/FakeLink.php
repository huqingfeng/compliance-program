<?php
/**
 * A structure for holding information on a hyperlink. It is unfortunate that this
 * exists, as it doesn't really have any benefit, but its a mistake I made a while
 * ago and haven't been able to remove yet. :p
 */
class FakeLink extends Link
{
    public function __toString()
    {
        return $this->getLinkText();
    }
}