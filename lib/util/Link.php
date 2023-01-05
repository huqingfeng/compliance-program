<?php
/**
 * A structure for holding information on a hyperlink. It is unfortunate that this
 * exists, as it doesn't really have any benefit, but its a mistake I made a while
 * ago and haven't been able to remove yet. :p
 */
class Link
{
    public function __construct($linktext, $link, $id = false, $target = '_self', $class = false, $textOnly = false)
    {
        $this->link = $link;
        $this->linktext = $linktext;
        $this->id = $id;
        $this->target = $target;
        $this->class = $class;
        $this->textOnly = $textOnly;
    }

    public function getLinkText()
    {
        return $this->linktext;
    }

    public function getLink()
    {
        return $this->link;
    }

    public function getHTML()
    {
        $id = $this->id ? ' id="'.$this->id.'" ' : '';

        $class = $this->class ? sprintf(' class="%s"', $this->class) : ' ';

        if ($this->textOnly){
            return '<span>'.$this->getLinkText().'</span>';
        } else {
            return '<a target="'.$this->target.'" href="'.$this->getLink().'" '.$id.$class.'>'.$this->getLinkText().'</a>';
        }

    }

    public function setLink($link)
    {
        $this->link = $link;
    }

    public function setLinkText($text)
    {
        $this->linktext = $text;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function __toString()
    {
        return $this->getHTML();
    }

    protected $target;
    protected $id;
    protected $link;
    protected $linktext;
    protected $class;
    protected $textOnly;
}