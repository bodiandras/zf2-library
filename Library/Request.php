<? 
namespace Library;

class Request extends \Zend\Http\PhpEnvironment\Request
{
    private $keys = array(
                        'p', //paginator page
                        'view', // followed by id
                        'id',
                        'hero',    
                    );
                    

    public function __construct($request = null)
    {            
        if($request) {
            $this->_clone($request);    
        }         
    }
    
    public function getUrlParams()
    {
        $url = $this->getRequestUri();
        
        $segments = explode('/', $url);
        
        $params = array();
        for($i = 0; $i < count($segments); $i++) {
            if(in_array($segments[$i], $this->keys)) $params[$segments[$i]] = $segments[$i+1];       
        } 
        
        return $params;  
    }
    
    private function _clone($request)
    {
        $objValues = get_object_vars($request); // return array of object values
        foreach($objValues AS $key => $value)
        {
             $this->$key = $value;
        }
    }    
} 