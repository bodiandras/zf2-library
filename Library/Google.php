<?
namespace Library;

class Google
{
	protected $sm;
	
	public function __construct($sm)
	{
		$this->sm = $sm;
		
	}
	
	public function getAccessToken()
	{
		$me = $this->sm->get('ReverseOAuth2\Google');
			
		$token = 'LOL';
		return $token;	
	}
	
	public function readSpreadSheet($spreadsheet_key)
	{
		$spreadsheet_url = 'https://docs.google.com/spreadsheets/d/' . $spreadsheet_key .'/export?gid=0&format=csv';
		if (($handle = fopen($spreadsheet_url, "r")) !== FALSE) {
		    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
		            $spreadsheet_data[]=$data;
		    }		    
	    	fclose($handle);
		}
		else return 'Problem reading spreadsheet.';
		
		return $spreadsheet_data;						
	}	
	
} 