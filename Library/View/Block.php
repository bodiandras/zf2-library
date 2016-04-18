<?
namespace Library;

class View_Block
{
    public $sm;
    public $cache = null;    
    public $cacheKey = '';
    public $cacheEnabled = true;        
    public $cacheOptions = array(
                            'adapter' => 'Apc',
                            'ttl' => 3600
                            );
    public $content;
    public $template;
    protected $paginator = null;
    public $totalRows = null;
    public $params;
   
    public function __construct($sm)
    {
        $this->sm = $sm;
        $this->initCache();          
    }
    
    public function render()
    {
        $html = '';
        if($this->cacheEnabled) {    
            $cacheKey = $this->getCacheKey();                             
            $html = $this->cache->getItem(cacheKey);               
            if($html) return $html;
        }
            
        $mView = new ViewModel($this->getData());               
        $mView->setTemplate($this->template);
        $mView->setVariable('params', $this->params);
        
        //$mView->setVariable('content', $viewRender->render($viewModel));        
                
        $html = $this->sm->get('ViewRenderer')->render($mView);
            
        if($this->cacheEnabled) $this->cache->setItem($this->cacheKey, $html);
            
        return $html;  
    }
    
    public function getData()
    {
        return array();   
    }
    
    public function initCache()
    {
        $cacheAdapter = '\Zend\Cache\Storage\Adapter\\'. $this->cacheOptions['adapter'];
        $this->cache  = new $cacheAdapter;
        $this->cache->getOptions()->setTtl($this->cacheOptions['ttl']);       
    }
    
    public function getCacheKey()
    {
        return $this->cacheKey;    
    }
    
    public function setCacheKey($key)
    {
        $this->cacheKey = $key; 
        return $this;   
    }
    
    public function setParams($params)
    {
        $this->params = $params;
        return $this;
    }
    
    public function setPaginator($paginator)
    {
        $this->paginator = $paginator;
        return $this; 
    }
    
    public function getTotalRows()
    {
        return $this->totalRows;    
    }        
}        