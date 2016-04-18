<?
namespace Library;

use Zend\Db\Metadata\Metadata;
use Zend\Db\Metadata\MetadataInterface;
use Zend\Db\TableGateway\Exception;
use Zend\Db\TableGateway\Feature;
use Zend\Db\Adapter\Adapter;

class Db_Metadata  
{
    private $_cache = null;
    
    private $_metadata = null;
    
    public function __construct(Adapter $adapter)
    {
        $this->_metadata = new \Zend\Db\Metadata\Metadata($adapter);
    }
    
    public function getCache()
    {
        if($this->_cache == null) {
            $this->_cache = \Zend\Cache\StorageFactory::factory(
                                array(
                                    'adapter' => array(
                                        'name' => 'filesystem',
                                        'options' => array(
                                            'dirLevel' => 2,
                                            'cacheDir' => 'data/cache/dbmetadata',
                                            'dirPermission' => 0755,
                                            'filePermission' => 0666,
                                            'namespaceSeparator' => '-db-'
                                        ),
                                    ),
                                    'plugins' => array('serializer'),
                                )
                            );            
        }
        return $this->_cache;
    }
      
    public function __call($name, $arguments)
    {
        if(method_exists ('Zend\Db\Metadata\MetadataInterface', $name)) {
            $key = $name . md5(serialize($arguments));
            $c = $this->getCache();
        
            if(!$c->hasItem($key)) {
                $v = call_user_func_array(array(&$this->_metadata,$name),$arguments);
                $c->addItem($key, $v);
            } 
            return $c->getItem($key);
        } else {
            return call_user_func_array(array(&$this->_metadata,$name),$arguments);    
        }
    }      
      
   
    
}