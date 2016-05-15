<?
namespace Library;

class View_Helper //extends \Zend\View\Helper
{
    protected $sm;
    
    public function __construct($sm)
    {
        $this->sm = $sm;
    } 
        
}   