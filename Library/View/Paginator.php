<?
namespace Library;

class View_Paginator
{
    public $sm;
    public $url = '';
    public $offset = 0;
    public $step = 10;
    public $totalRows = 100;
    
    public function __construct($sm, $params)
    {
        $this->sm = $sm;        
                          
    }
    
    public function setParams($params)
    {
        $this->url = (isset($params['url'])) ? $params['url'] : $this->url;
        $this->offset = (isset($params['offset'])) ? $params['offset'] : 0;
        $this->step = (isset($params['step'])) ? $params['step'] : $this->step;        
        
        return $this;   
    }
    
    public function render()
    {
            
        $html = '<div class="pagination-delimiter">
                    <ul class="pagination pagination-style-2">
                        <li class="disabled"><a href="'.$this->url.'">«</a></li>';
                        
       while($i++ < $this->totalRows / $this->step) {
           $class = ($i == $this->offset) ? 'active' : '';           
           $html .=  '<li class="'.$class.'"><a href="'.$this->url.'/p/'.$i.'">'.$i.'</a></li>';     
       }
           
                        
               
       $html .='</ul>
       
                </div>';
       return $html;             
    }
    
    public function setTotalRows($totalRows)
    {
        $this->totalRows = $totalRows;    
    }
}