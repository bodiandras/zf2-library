<?
namespace Library;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\RowGateway\RowGateway;
use Zend\Db\TableGateway\Feature\RowGatewayFeature as RowGatewayFeature;

class Db_Row  extends RowGateway
{
    public $tableClass;
    
    const RESULT_SUCCESS = 1;    
        
    public function _canAccess($write = false)
    {
        if(!Auth::getInstance()->hasIdentity()) { 
            // can't do much checking without a logged in user
            // security relies on ACL and specialized controller 
            // or model logic in this case
            return true;
        } else {
            return $this->canAccess($write); // delegate to classes            
        }         
    }
    
    public function getTable()
    {                
        return new $this->tableClass;
    }
    	
	public function getTableName()
	{
		return $this->table;
	}
	
    public function getData()
    {
        return $this->data;
    }
    
    public function update($data)
    {
        foreach($data as $k => $d) {
            if($this->$k != $d) $this->$k = $d;
        }
        $this->save();
    }    
}  