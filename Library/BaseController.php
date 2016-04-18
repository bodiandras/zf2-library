<?
namespace Library;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Mvc\MvcEvent;

class BaseController extends AbstractActionController
{
    public $sm;
	public function onDispatch(MvcEvent $e)
    {
    	$action = $this->params('action');
        $controller = $this->params('controller');
        $request = $e->getRequest();
        $query = $this->params()->fromQuery();
        $this->sm = $this->getServiceLocator();
        
        if($request->isXmlHttpRequest()) {          
            $layout = $this->layout();
            $layout->setTemplate('layout/empty');            
        }       
        
    	$result = parent::onDispatch($e);
    	return $result;
    }
    
    public function getUrl()
    {
        $routeMatch = $this->sm->get('Application')->getMvcEvent()->getRouteMatch();
        $routeName = $routeMatch->getMatchedRouteName();
        $url = $this->url()->fromRoute($routeName, array());
        return $url;
    }
    
    public function leftAction()
    {
    	$left = array();    	
    	$controller = $this->params('controller');    	
    	switch($controller) {
    		case 'Application\Controller\D3\D3':
    		case 'Application\Controller\D3\Maya':
    		case 'Application\Controller\D3\Unity':
    			$leftView = new ViewModel($left);
    			$leftView->setTemplate('application/d3/left');
    			break;
    	} 	
    	
    	if($leftView) {
    		$leftView->setCaptureTo('left');
    		$this->layout()->addChild($leftView, 'left');
    	}
    }
}