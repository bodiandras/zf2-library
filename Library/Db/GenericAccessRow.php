<?
namespace Library;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;
use Zend\Db\RowGateway\RowGateway;
use Zend\Db\TableGateway\Feature\RowGatewayFeature as RowGatewayFeature;
use Model;
use Zfe;

global $_genericAccessRowCache; // cache stored in global vars for now until a better way is implemented
global $hits;
global $mises;

class Db_GenericAccessRow  extends Db_Row
{
    public $enableDiskCache = 1;

    public function getTable()
    {
        return strtolower($this->table);
    }

    public function getPrimaryKey()
    {
        return $this->primaryKeyColumn[0];
    }
    
    public function getAllStatusObjects()
    {
        $pk = $this->primaryKeyColumn[0];
        $pkv = $this->$pk;
        $coNames = array('clusters' => 'ClusterCycleStatus', 'modules' => "ModuleCycleStatus", "initiatives" => "InitiativeCycleStatus", "instances" => "InstanceCycleStatus");
        $table = 'Model\\' . $coNames[strtolower($this->table)];
        $objects = new $table();
        $select = "($pk=$pkv)";
        if($this->table == 'initiatives') {
            $select .= " and (active='1')";
        }
        $object = $objects->select($select);
        return $object;
    }
    
    public function getCycleStatusObject($cycle) 
    {
        $pk = $this->primaryKeyColumn[0];
        $pkv = $this->$pk;
        $coNames = array('clusters' => 'ClusterCycleStatus', 'modules' => "ModuleCycleStatus", "initiatives" => "InitiativeCycleStatus", "instances" => "InstanceCycleStatus");
        $table = 'Model\\' . $coNames[strtolower($this->table)];
        $objects = new $table();
        $select = "($pk=$pkv) and (cycleid=".$cycle->cycleid.")";
        if($this->table == 'initiatives') {
            $select .= " and (active='1')";
        }
        
        $object = $objects->select($select);
        if(count($object)) {
            return $object->current();
        } else {
            return $objects->insertRow(array($pk => $pkv, 'cycleid' => $cycle->cycleid));
        }
    }
    
    public function recomputeFinancialStatus()
    {       
        if($this->table == "organizations") {
            return;
        }
        $this->financialstatus = null;
        $this->getFinancialStatus();
        $cos = $this->getAllStatusObjects();
        $cycles = new Model\Cycles();
        foreach($cos as $co) {
            $co->totalstatus = null;
            $co->save();
            $this->getTotalStatus($cycles->findById($co->cycleid));
        }
    }
    
    public function recomputeActivityStatus($cycle)
    {
        if($this->table == "organizations") {
            return;
        }        
        $co = $this->getCycleStatusObject($cycle);
        $co->activitystatus = null;
        $co->totalstatus = null;
        $co->save();
        $this->getActivityStatus($cycle);
        $this->getTotalStatus($cycle);
        $parent = $this->getParent();
        if(!is_null($parent)) {
            $parent->recomputeActivityStatus($cycle);
        }
    }
    
    public function getMyObjectList()
    {
        $objectName = 'Model\\' . ucwords($this->table);
        return new $objectName();
    }
    
    public function getMyObject()
    {
        $objects = $this->getMyObjectList();
        $pk = $this->primaryKeyColumn[0];
        $pkv = $this->$pk;
        return $objects->findById($pkv);        
    }
    
    public function getTotalStatus($cycle)
    {
        $co = $this->getCycleStatusObject($cycle);
        if(is_null($co->totalstatus)) {            
            if(is_null($co)) {
                return;
            }            
            $co->totalstatus = $this->computeTotalStatus($cycle);
            $co->save();                          
        }
        return $co->totalstatus;
    }
    
    public function computeTotalStatus($cycle)
    {
        
        $financial = $this->getFinancialStatus();
        $activity = $this->getActivityStatus($cycle);
        //echo "<hr>" . $financial;
        //echo "<hr>" . $activity;
        //echo "<hr>";
        if($financial == -1) {
            return $activity;
        }
        if($financial + $activity == 4) {
            return 2;
        }
        if(($activity == 1) && ($financial == 2)) {
            return 2;
        }    
        if(($activity == 0) && ($financial == 2)) {
            return 1;
        }
        if(($activity == 2) && ($financial == 1)) {
            return 1;
        }         
        if(($activity == 1) && ($financial == 1)) {
            return 1;
        }         
        if($financial + $activity <= 2) {
            return 0;
        }
        return -1;
    }
    
    
    public function getActivityStatus($cycle) 
    {
        $co = $this->getCycleStatusObject($cycle);
        if(is_null($co->activitystatus)) {
            if(is_null($co)) {
                return;
            }
            $co->activitystatus = $this->computeActivityStatus($cycle);
            $co->save();
        }
        return $co->activitystatus;
    }
    
    public function computeActivityStatus($cycle)
    {
        $initiatives = $this->getInitiatives();
        $weights = array();
        $statuses = array();
        $partialTotal = 0;
        $grandTotal = 0;
        $partialCount = 0;
        foreach($initiatives as $k => $initiative) {
            $statuses[$initiative->initiativeid] = $initiative->getActivityStatus($cycle);
            if($statuses[$initiative->initiativeid] == -1) {
                continue;
            }
            
            $mtr = $initiative->getMetricsTotalRow();
            if(is_null($mtr)) {
                $weights[$initiative->initiativeid] = -1;
            } else {
                $weights[$initiative->initiativeid] = abs($mtr['AtEnd_BudgetDue']);
                $partialTotal += abs($mtr['AtEnd_BudgetDue']);
                $partialCount++;
            }
        }
        if($partialCount == 0) { // no initiative has budget... can't roll up
            return -1;
        }
        $avgBudget = $partialTotal/$partialCount;
        foreach($weights as $key => $value) {
            if($value == -1) {
                $weights[$key] = .5 * $avgBudget;
                $grandTotal += .5 * $avgBudget;
            } else {
                $grandTotal += $value;
            }
        }
        $code = 0;
        foreach($initiatives as $initiative) {
            if($statuses[$initiative->initiativeid] == -1) {
                continue;
            }
            $code += $statuses[$initiative->initiativeid] * $weights[$initiative->initiativeid];
        }
        $code = $code / $grandTotal;
        return round($code);
    }
    
    public function hasFinancialInformation()
    {
        $r = $this->getMetricsTotalRow();
        if(is_null($r)) {
            return 0;
        } else {
            return 1;
        }
    }   
     
    public function getMetricsTotalRow()
    {
        $pk = $this->getPKForAccess();
        $pkv = $this->$pk;
        $pm = $this->getPrimitiveMap();
        $recordLevel = ucwords($pm[$this->table]);
        
        $instances = new Model\Instances();
        
        $select = "SELECT  AtActuals_Date, AtEnd_BudgetDue, AtEnd_ActualsForecast_vBudgetDue FROM metrics_sums_total WHERE ($pk = $pkv) and (recordview='current') and (RecordLevel='$recordLevel') and (AtStart_Date<'2050-01-01')";
        $adapter = $instances->getAdapter();
        $rows = $adapter->createStatement($select)->execute();            
        if(!count($rows)) {
            return null;
        }
        return $rows->current();        
    }
    
    public function getFinancialStatus()
    {
        if(is_null($this->financialstatus)) {            
            $mo = $this->getMyObject();
            if(is_null($mo)){
                return -1;
            }             
            $mo->financialstatus = $this->computeFinancialStatus();
            $mo->save();
            $this->financialstatus = $mo->financialstatus;  
        }
        return $this->financialstatus;
    }
    
    public function computeFinancialStatus()
    {
        $data = $this->getMetricsTotalRow();
        if(is_null($data)) {
            return -1;
        }
        //print_r($data);
        $budget = abs($data['AtEnd_BudgetDue']);
        $shortfall = $data['AtEnd_ActualsForecast_vBudgetDue'];
        $latestActuals = $data['AtActuals_Date'];                         
        $pp = 0;
        if($shortfall < 0) {
            $pp = abs($shortfall) / abs($budget);
        }        

        $code = -1;
        if($pp >= .3) {
            $code = 0;
        } elseif($pp < .1) {
            $code = 2;
        } else {
            $code = 1;
        }
        
        if($code > 0) {
            $dt = strtotime($latestActuals);
            $m2a = strtotime('2 months ago');
            if($dt < $m2a) {
                $code -= 1;
            }
        }
        return $code;
    }
    
    public static function getDisplayStatus($cycleStatus)
    {
    	if($cycleStatus->editstatus!='submitted') {
    		return 'Tracking due';
    	} else {
    		return 'Submitted';
    	}
    }
    
    public function populate(array $rowData, $rowExistsInDatabase = false)
    {
        $r = RowGateway::populate($rowData, $rowExistsInDatabase); // we disable row level in this case, as it's moved to the table level
        return $r;
    }
    
    public function getPrimitiveMap()
    {
        return array("instances" => "instance", "clusters" => "cluster", "modules" => "module", "initiatives" => "initiative", "organizations" => "organization");   
    }
    
    public function canAccess($write = false)
    {        
        //return true; // disable access checks for now
        if(!Auth::getInstance()->hasIdentity()) { 
            // can't do much checking without a logged in user
            // security relies on ACL and specialized controller 
            // or model logic in this case
            return true;
        }
        if($write == true) {
            return true; // handled at model / controller level
        } else {
            $primtiveMap = $this->getPrimitiveMap();
            
            $primitive = $primtiveMap[$this->table] . ":read-basic";
            return $this->hasAccess($primitive);
        }            
    }
    
    public function getCacheObject()
    {
        /*$cid = str_replace(":", "_", $this->getCacheIdentifier());
        $cid = str_replace("(", "_", $cid);
        $cid = str_replace(")", "_", $cid);*/
        $cid = $this->getCacheIdentifier();
        return \Zend\Cache\StorageFactory::factory(
                                array(
                                    'adapter' => array(
                                        'name' => 'filesystem',
                                        'options' => array(
                                            'namespace' => $cid,                                        
                                            'dirLevel' => 2,
                                            'cacheDir' => 'data/cache/access',
                                            'dirPermission' => 0755,
                                            'filePermission' => 0666,
                                            'namespaceSeparator' => '-ac-'
                                        ),
                                    ),
                                    'plugins' => array('serializer'),
                                )
                            );            
        
        
    }
              
    public function removeCache()
    {               
        $this->removeParentCache();
        $this->removeChildrenCache();
    }              
    
    public function removeParentCache()
    {
        $this->removeMyCache();
        $parent = $this->getParent();
        if(!is_null($parent)) {
            $parent->removeParentCache();
        }        
    }
    
    public function removeChildrenCache()
    {
        $this->removeMyCache();
        $children = $this->getChildren();
        foreach($children as $child) {
            $child->removeChildrenCache();
        }
    }
    
    public function removeMyCache()
    {        
        $this->getCacheObject()->clearByNamespace($this->getCacheIdentifier());            
    }
              
    public function setCache($name, $data, $toDisk = 0)
    {
        global $_genericAccessRowCache;
        $_genericAccessRowCache[$name] = $data;
        if(($toDisk == 1) && ($this->enableDiskCache == 1)) {
            $this->getCacheObject()->addItem(md5($name), $data);       
        }
    }                
    
    public function getCache($name, $fromDisk = 0)
    {    
        global $hits;
        global $mises;        
        global $_genericAccessRowCache;
        if(array_key_exists($name, $_genericAccessRowCache)) {
            $hits++;
            return $_genericAccessRowCache[$name];
        } else {
            $mises++;
            if($fromDisk == 1) {
                $co = $this->getCacheObject();
                if($co->hasItem(md5($name))) {
                    return $co->getItem(md5($name));
                }
            }
            return null;
        }
    }    
    
    public function getHumanId()
    {
        if($this->table == "organizations") {
            return "-";
        }
        if($this->table == "instances") {
            return "-";
        }
        if($this->table == "clusters") {
            return $this->order;
        }
        if($this->table == "modules") {
            return $this->getCluster(1)->order . "." . $this->order;
        }
        if($this->table == "initiatives") {
            $module = $this->getModule(1);
            $cluster = $module->getCluster(1);
            //$myPos =
            $initiatives = new Model\Initiatives();
            $adapter = $initiatives->getAdapter();
            $select = "select count(*) as cnt from initiatives where active=1 and moduleid=" . $this->moduleid . " and initiativeid<" . $this->initiativeid . " and editstatus!='deleted'";
            $r = $adapter->createStatement($select)->execute();            
            $r = $r->current();
            return $cluster->order . "." . $module->order . "." . ($r['cnt'] + 1);
        }
    }
    
    public function hasAnyAccess($primitiveList)
    {
        if(Zfe\Auth::getInstance()->getIdentity()->role == "super") {
            return true;
        }
        $cacheId = $this->getCacheIdentifier() . "::hasAnyAccess(".$primitive.")";
       
        if(!is_null($this->getCache($cacheId))) {
            return $this->getCache($cacheId);
        }       
        $userId = Zfe\Auth::getInstance()->getIdentity()->userid;
        $primitives = $this->getAccess($userId);         
		
        foreach($primitiveList as $primitive) {
            if(!$primitives[$primitive]) {
                $primitives[$primitive] = 0;
            }            
            $rv = $primitives[$primitive]; 
            if(!$rv) { // check for super rights
                $checkTable = explode(":", $primitive);
                $checkTable = $checkTable[0]; 
                $superPrimitive = $checkTable . ":super";
                $rv = $primitives[$superPrimitive];
            } 
            if($rv) {
                $this->setCache($cacheId, $rv);
                return $rv;
            }            
        }
        $this->setCache($cacheId, $rv);
        return $rv;
    }
    
	/*
	 * Method to check if user has a specific access on object
	 */
	public function hasAccess($primitive)
	{		            
        if(Zfe\Auth::getInstance()->getIdentity()->role == "super") {
            return true;
        }
        $cacheId = $this->getCacheIdentifier() . "::hasAccess(".$primitive.")";
        //echo "\n<br>HA: " . $cacheId;flush();
        
        if(!is_null($this->getCache($cacheId))) {
            return $this->getCache($cacheId);
        }       
        $userId = Zfe\Auth::getInstance()->getIdentity()->userid;
        $primitives = $this->getAccess($userId);         
		//return true;
        if(!$primitives[$primitive]) {
            $primitives[$primitive] = 0;
        }
        
        $rv = $primitives[$primitive];
        if(!$rv) { // check for super rights
            $checkTable = explode(":", $primitive);
            $checkTable = $checkTable[0];           
            $superPrimitive = $checkTable . ":super";          
            $rv = $primitives[$superPrimitive];
        } 
        $this->setCache($cacheId, $rv);
        return $rv;
	}    
    
    public function getCacheIdentifier()
    {
        $pk = $this->getPKForAccess();
        $pkv = $this->$pk;
        return $this->table . "(".$pkv.")";
    }
    
    public function getAccessUsers()
    {
        $pk = $this->getPKForAccess();
        $users = new Model\Users();        
        $select = $users->getSql()->select();
		$select
		->join('access', 'access.userid = users.userid', array('groupname'))
		->join('groups', 'access.groupname = groups.groupname', array('display'))
		->join('organizations', 'users.organizationid=organizations.organizationid', array('name'))
		->where(array('objecttype'=>$this->table, 'objectid'=>$this->$pk));
        
        $users = $users->selectWith($select);
        
        $rv = array();
        foreach($users as $user) {        	
            $rv[$user->groupname][] = $user;            
        }
        return $rv;
    }
    
    
	public function getPKForAccess()
    {
   	    switch($this->table) {
			case 'initiatives': $pk = 'initiativeid'; break;
			default: $pk = $this->primaryKeyColumn[0]; break;
		}
        return $pk;
    }
    
    public function grant($userId, $accessPrimitive)
	{
		switch($this->table) {
			case 'initiatives': $pk = 'initiativeid'; break;
			default: $pk = $this->primaryKeyColumn[0]; break;
		}		
		$pkv = $this->$pk;
		$params = array('userid' => $userId, 'groupname' => $accessPrimitive, 'objecttype' => $this->table, 'objectid' => $pkv);
		$access = new Model\Access();
		$access->_grant($params);
        $this->removeCache();        		
	}
    
	public function revoke($userId=null, $accessPrimitive=null)
	{
		if(is_null($userId) && is_null($accessPrimitive)) {
			throw new \Exception('Revoke must receive either userid or accessprimitive');	
		}
		$pk = $this->primaryKeyColumn[0];
		
		if($this->table == 'initiatives') {
            $pk = 'initiativeid';
        }
				
		$pkv = $this->$pk;
        
		$params = array('objecttype' => $this->table, 'objectid' => $pkv);
		
		if(!is_null($userId)) {
			$params['userid'] = $userId;
		}
		if(!is_null($accessPrimitive)) {
			$params['groupname'] = $accessPrimitive;
		}

		$access = new Model\Access();		
		$access->_revoke($params);
        $this->removeCache();        		
	}
	

	
    // return a list of all the primitives directly assigned to me
    public function getPrimitives($userId)
    {
        $cacheId = $this->getCacheIdentifier() . "::getPrimitives(".$userId.")";
        if($this->getCache($cacheId)) {
            return $this->getCache($cacheId);
        }


        $primitives = array();
        $pk = $this->getPKForAccess();
        // see if I have any entry in access
        $access = new Model\Access();
        $access = $access->select(array("objecttype" => $this->table, "objectid" => $this->$pk, "userid" => $userId))->current();
        if($access) {
            $myGroup = $access->getGroup();
            if($myGroup) {        
                $primitives[] = array(1, $myGroup->getPrimitives());
            }
            if(strlen(trim($access->addprimitives))) {
                $primitives[] = array(1, explode(",", $access->addprimitives));
            }
            if(strlen(trim($access->removeprimitives))) {
                $primitives[] = array(-1, explode(",", $access->removeprimitives));
            }
        }        
        $this->setCache($cacheId, $primitives);
        return $primitives;
    }
    
    public function getHierarchy()
    {
        return array('organizations' => 0, 'instances' => 1, "clusters" => 2, "modules" => 3, "initiatives" => 4);
    }

    public function getHierarchyPk()
    {
        return array('organizationid', 'instanceid', "clusterid", "moduleid", "initiativeid");
    }
    
    // returns my parent
    public function getParent()
    {        
        $cacheId = $this->getCacheIdentifier() . "::getParent()";
        if($this->getCache($cacheId)) {
            return $this->getCache($cacheId);
        }        
        
        $h = $this->getHierarchy();
        $hp = $this->getHierarchyPk();
        $myPos = $h[$this->table];
        if($myPos == 0) { // I'm an organization, I have no parent
            $rv = null;
        } else {
            $fk = $hp[$myPos - 1];
            $hv = array_keys($h);
            $table = $hv[$myPos - 1];
            $objectName = 'Model\\' . ucwords($table);
            $objects = new $objectName();
            $objects->_disableReadChecks = 1;
            $object = $objects->findById($this->$fk);
            $rv = $object;            
        } 
        $this->setCache($cacheId, $rv);
        return $rv;
    }
    
    // get all the initiatives contained by object, regardless of the level
    public function getInitiatives()
    {
        $pk = $this->getPKForAccess();
        $pkv = $this->$pk;
        if($this->table == "initiatives") {
            return array($this);
        } else {     
            $children = $this->getChildren();
            $rv = array();
            foreach($children as $child) {
                $a = $child->getInitiatives();
                foreach($a as $i) {
                    $rv[] = $i;
                }
            }
            return $rv;
        }
    }
    
    // returns my children
    public function getChildren()
    {        
        $cacheId = $this->getCacheIdentifier() . "::getChildren()";
        if($this->getCache($cacheId)) {
            return $this->getCache($cacheId);
        }            
        $h = $this->getHierarchy();
        $hp = $this->getHierarchyPk();
        $myPos = $h[$this->table];
        if($myPos == 4) { // I'm an initiative, I have no children
            $rv = array();
        } else {
            $fk = $hp[$myPos];
            $hv = array_keys($h);
            $table = $hv[$myPos +1];
            $objectName = 'Model\\' . ucwords($table);
            $objects = new $objectName();
            $objects->_disableReadChecks = 1;            
            $selectArray = array($fk => $this->$fk);
            if($objects->table == "initiatives") {
                $selectArray['active'] = 1; 
            }
            $children = $objects->select($selectArray);
            $rv = array();
            foreach($children as $child) {
                $rv[] = $child;
            }
        }
        $this->setCache($cacheId, $rv);
        return $rv;                 
    }
    
    // receives an array of primitive arrays
    // joins them and returns a single primitives array
    public function joinPrimitives($primitives)
    {
        $neg = array();
        $rv = array();
        foreach($primitives as $primitive) {
            if($primitive[0] == -1) {
                $neg[] = $primitive;
                continue;
            }
            foreach($primitive[1] as $item) {
                $rv[$item] = 1;
            }
        }
        foreach($neg as $primitive) {
            foreach($primitive[1] as $item) {
                unset($rv[$item]);
            }
        }
        return $rv;   
    }

    /** Return a union of all my primitives, both mine directly or inherited
     * 
     * $bubble - 0  - initial call
     *           1  - bubble up, recursive call from an object below - no need to check children
     *           -1 - bubble down, recursive call from an object above - no need to check parents
     * 
     * returns an array of primitives   
    */
    public function getAccess($userId, $bubble = 0)
    {        
        $cacheId = $this->getCacheIdentifier() . "::getAccess($userId - $bubble)";
        $disk = 0;
        if($bubble == 0) {
            $disk = 1;
        }
        $gotC = $this->getCache($cacheId, $disk);
        if($gotC) {
            return $gotC;
        }
        $primitives = $this->_getAccess($userId, 0);
        
        $access = $this->joinPrimitives($primitives);
        $this->setCache($cacheId, $access, $disk);
        return $access;        
    }
    
    public function _getAccess($userId, $bubble = 0)
    {        
        $cacheId = $this->getCacheIdentifier() . "::_getAccess($userId - $bubble)";
        if($this->getCache($cacheId)) {
            return $this->getCache($cacheId);
        }                  
        $primitives = $this->getPrimitives($userId);
        if($bubble != -1) {
            $parent = $this->getParent();
            if($parent) {
                $rp = $parent->_getAccess($userId, 1);
                foreach($rp as $r) {
                    $primitives[] = $r;
                } 
            }
        }
        if($bubble != 1) {
            $children = $this->getChildren();
            foreach($children as $child) {
                $rp = $child->_getAccess($userId, -1);
                foreach($rp as $r) {
                    $primitives[] = $r;
                } 
            }
        }

            /*echo "<hr>" . $this->table . "  =>  " . $this->getPKForAccess() . " => ";
            $pk = $this->getPKForAccess();
            echo $this->$pk;
            print_rt($primitives);*/

        $this->setCache($cacheId, $primitives);
        return $primitives;        
    }
}  