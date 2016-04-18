<?php

namespace Library;



class ViewModel extends \Zend\View\Model\ViewModel
{
    private $sm = null;
    public $messages = array();
    
    public function __construct($variables = null, $sm = null)
    {
        $this->init();
            
        if($sm) $this->$sm = $sm;
        
        return parent::__construct($variables);
    }
    
    public function init()
    {
        
    }
    
    public static function getClone($object)
    {
        $clone = new ViewModel();
        foreach($object as $property => $value) {
            $clone->$property = $value;
        }
        return $clone;
    }
    
    public function addMessages($messages)
    {
        foreach($messages as $message) {
            $this->addMessage($message);
        }
    }
    
    public function addMessage($message)
    {
        $this->messages []= $message;
    }
    
    public function showMessage($message)
    {        
        return '<span class="'.$message['class'].'">'.$message['text'].'</span>';
    }
    
    public function showMessages()
    {
        $html = '';
        foreach($this->messages as $message) {
            $html .= $this->showMessage($message);
        }
        return $html;
    }
    
}