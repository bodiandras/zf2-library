<?php
require_once '../lib/Kendo/Autoload.php';

require_once '../include/header.php';

$states = array('Alabama', 'Alaska', 'American Samoa', 'Arizona', 'Arkansas', 'California',
            'Colorado', 'Connecticut', 'Delaware', 'District of Columbia', 'Florida', 'Georgia',
            'Guam', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky',
            'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota',
            'Mississippi', 'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire',
            'New Jersey', 'New Mexico', 'New York', 'North Carolina', 'North Dakota',
            'Northern Marianas Islands', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania',
            'Puerto Rico', 'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee',
            'Texas', 'Utah', 'Vermont', 'Virginia', 'Virgin Islands', 'Washington',
            'West Virginia', 'Wisconsin', 'Wyoming');

$dataSource = new \Kendo\Data\DataSource();
$dataSource->data($states);

$autoComplete = new \Kendo\UI\AutoComplete('states');
$autoComplete->dataSource($dataSource)
    ->change('change')
    ->select('select')
    ->open('open')
    ->close('close')
    ->filtering('filtering')
    ->dataBound('dataBound')
    ->attr('style', 'width: 100%;');

?>
<div class="demo-section k-content">
    <h4>Select a state in USA:</h4>
<?php
echo $autoComplete->render();
?>
</div>
<div class="box">                
    <h4>Console log</h4>
    <div class="console"></div>
</div>

<script>
    function open() {
        kendoConsole.log("event :: open");
    };

    function close() {
        kendoConsole.log("event :: close");
    };

    function change() {
        kendoConsole.log("event :: change");
    };

    function dataBound() {
        kendoConsole.log("event :: dataBound");
    };

    function filtering() {
        kendoConsole.log("event :: filtering");
    };

    function select(e) {
        if ("kendoConsole" in window) {
            var dataItem = this.dataItem(e.item.index());
            kendoConsole.log("event :: select (" + dataItem + ")" );
        }
    };
</script>
<?php require_once '../include/footer.php'; ?>
