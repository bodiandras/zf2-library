<?
namespace Library;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\RowGateway\RowGateway;
use Zend\Db\TableGateway\Feature;
use Zend\Db\Sql\Select;

class Db_Table  extends TableGateway
{
	protected $table = null;
	protected $primaryKey = null;
	protected $rowClass = 'Library\Db_Row';
	public    $_disableReadChecks = 1;
    public $_adapter;
    
    const RESULT_SUCCESS = 1;
	
	public function __construct()
	{
		if($this->adapter) {
		  $this->_adapter = $adapter = $this->adapter;
		} else {
		  $this->_adapter = $adapter = Feature\GlobalAdapterFeature::getStaticAdapter();  
		}        
		$resultSetPrototype = new ResultSet();
		$resultSetPrototype->setArrayObjectPrototype(new Db_Row($this->primaryKey, $this->table, $adapter));
		$row = new $this->rowClass($this->primaryKey,  $this->table, $adapter);
		parent::__construct($this->table, $adapter, array(new Feature\RowGatewayFeature($row), new Db_CachedMetadataFeature() /*Feature\MetadataFeature()*/ ), $resultSetPrototype);
	}
    
    public function createRow()
    {
        $resultSet = $this->getResultSetPrototype();
        $newRow = clone $resultSet->getArrayObjectPrototype();

        return $newRow;
    }
    
    public function find($id)
    {
        return $this->select(array($this->primaryKey => $id))->current();    
    }
}