<?
namespace Library;

class View_Table extends ViewModel
{
   
    public function __construct($title, $columns, $data)
    {        
        $this->setTemplate('view-table');
               
        $view = array(
                    'title' => $title,
                    'columns' => $columns,
                    'data' => $data,
        );
        return parent::__construct($view);    
    }
        
}        