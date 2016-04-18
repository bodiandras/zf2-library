<?php
require_once '../lib/Kendo/Autoload.php';
require_once '../include/header.php';
?>

<div class="demo-section k-content">
    <h4>Pick a color</h4>
<?php

$dataSource = new \Kendo\Data\DataSource();
$dataSource->data(array( 'Red-violet', 'Red', 'Red-orange', 'Orange', 'Yellow-orange',
                        'Yellow', 'Yellow-green', 'Green', 'Blue-green', 'Blue',
                        'Blue-violet', 'Violet'));

$autoComplete = new \Kendo\UI\AutoComplete('input');
$autoComplete->dataSource($dataSource)
             ->attr('style', 'width: 100%;');

echo $autoComplete->render();
?>
    <div class="demo-hint">Hint: type "red"</div>
</div>

<div class="box wide">
    <div class="box-col">
        <h4>Set / Get Value</h4>
        <ul class="options">
            <li>
                <input id="value" type="text" class="k-textbox" />
                <button id="set" class="k-button">Set value</button>
            </li>
            <li style="text-align: right;">
                <button id="get" class="k-button">Get value</button>
            </li>
        </ul>
    </div>
    <div class="box-col">
        <h4>Find item</h4>
        <ul class="options">
            <li>
                <input id="word" value="B" class="k-textbox" />
                <button id="search" class="k-button">Search</button>
            </li>
        </ul>
    </div>
</div>

<script>
    $(function() {
        var autocomplete = $("#input").data("kendoAutoComplete"),
            setValue = function(e) {
                if (e.type != "keypress" || kendo.keys.ENTER == e.keyCode)
                    autocomplete.value($("#value").val());
            },
            setSearch = function(e) {
                if (e.type != "keypress" || kendo.keys.ENTER == e.keyCode)
                    autocomplete.search($("#word").val());
            };

        $("#set").click(setValue);
        $("#value").keypress(setValue);
        $("#search").click(setSearch);
        $("#word").keypress(setSearch);

        $("#get").click(function() {
            alert(autocomplete.value());
        });
    });
</script>
<style>
    .box .k-textbox {
        width: 80px;
    }
    .box .k-button {
        min-width: 80px;
    }
</style>

<?php require_once '../include/footer.php'; ?>
