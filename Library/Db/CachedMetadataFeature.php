<?
namespace Library;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Metadata\MetadataInterface;
use Zend\Db\TableGateway\Exception;
use Zend\Db\TableGateway\Feature;

class Db_CachedMetadataFeature extends Feature\MetadataFeature
{
    /**
     * @var MetadataInterface
     */
    protected $metadata = null;

    /**
     * Constructor
     *
     * @param MetadataInterface $metadata
     */
    public function postInitialize()
    {
        $this->metadata = new Db_Metadata($this->tableGateway->adapter);
        parent::postInitialize();        
    }


}