<?php
require_once '../lib/Kendo/Autoload.php';
require_once '../lib/DataSourceResult.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    header('Content-Type: application/json');

    $result = new DataSourceResult('sqlite:..//sample.db');

    echo json_encode($result->read('Customers', array('ContactName', 'CustomerID', 'CompanyName')));

    exit;
}
?>
<?php
require_once '../include/header.php';

$read = new \Kendo\Data\DataSourceTransportRead();
$read->url('template.php')
     ->type('POST');

$transport = new \Kendo\Data\DataSourceTransport();
$transport->read($read);

$schema = new \Kendo\Data\DataSourceSchema();
$schema->data('data')
       ->total('total');

$dataSource = new \Kendo\Data\DataSource();
$dataSource->transport($transport)
           ->schema($schema);

$autoComplete = new \Kendo\UI\AutoComplete('customers');
$autoComplete->minLength(1)
             ->dataTextField('ContactName')
             ->dataSource($dataSource)
             ->height(370)
             ->attr('style', 'width:400px')
             ->headerTemplate(<<<TEMPLATE
<div class="dropdown-header k-widget k-header"><span>Photo</span><span>Contact info</span></div>
TEMPLATE
            )
             ->template(<<<TEMPLATE
<span class="k-state-default" style="background-image: url('../content/web/Customers/#= CustomerID #.jpg')"></span><span class="k-state-default"><h3>#: data.ContactName #</h3><p>#: data.CompanyName #</p></span>
TEMPLATE
            );
?>

<div class="demo-section k-content">
    <h4>Customers</h4>
<?php
echo $autoComplete->render();
?>
    <p class="demo-hint">
        Start typing to find a customer. E.g. "Ann"
    </p>
</div>

<style>
    .dropdown-header {
        border-width: 0 0 1px 0;
        text-transform: uppercase;
    }

    .dropdown-header > span {
        display: inline-block;
        padding: 10px;
    }

    .dropdown-header > span:first-child {
        width: 50px;
    }

    #customers-list .k-item {
        line-height: 1em;
        min-width: 300px;
    }
    
    /* Material Theme padding adjustment*/
    
    .k-material #customers-list .k-item,
    .k-material #customers-list .k-item.k-state-hover,
    .k-materialblack #customers-list .k-item,
    .k-materialblack #customers-list .k-item.k-state-hover {
        padding-left: 5px;
        border-left: 0;
    }

    #customers-list .k-item > span {
        -webkit-box-sizing: border-box;
        -moz-box-sizing: border-box;
        box-sizing: border-box;
        display: inline-block;
        vertical-align: top;
        margin: 20px 10px 10px 5px;
    }

    #customers-list .k-item > span:first-child {
        -moz-box-shadow: inset 0 0 30px rgba(0,0,0,.3);
        -webkit-box-shadow: inset 0 0 30px rgba(0,0,0,.3);
        box-shadow: inset 0 0 30px rgba(0,0,0,.3);
        margin: 10px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background-size: 100%;
        background-repeat: no-repeat;
    }

    #customers-list h3 {
        font-size: 1.2em;
        font-weight: normal;
        margin: 0 0 1px 0;
        padding: 0;
    }

    #customers-list p {
        margin: 0;
        padding: 0;
        font-size: .8em;
    }
</style>
<?php require_once '../include/footer.php'; ?>
