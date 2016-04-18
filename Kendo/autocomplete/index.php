<?php
require_once '../include/header.php';
require_once '../lib/Kendo/Autoload.php';
?>
<div class="demo-section k-content">
    <h4>Choose shipping countries:</h4>
    <?php
    $countries = array('Albania', 'Andorra', 'Armenia', 'Austria', 'Azerbaijan', 'Belarus', 'Belgium',
        'Bosnia & Herzegovina', 'Bulgaria', 'Croatia', 'Cyprus', 'Czech Republic', 'Denmark', 'Estonia',
        'Finland', 'France', 'Georgia', 'Germany', 'Greece', 'Hungary', 'Iceland', 'Ireland', 'Italy',
        'Kosovo', 'Latvia', 'Liechtenstein', 'Lithuania', 'Luxembourg', 'Macedonia', 'Malta', 'Moldova',
        'Monaco', 'Montenegro', 'Netherlands', 'Norway', 'Poland', 'Portugal', 'Romania', 'Russia',
        'San Marino', 'Serbia', 'Slovakia', 'Slovenia', 'Spain', 'Sweden', 'Switzerland', 'Turkey',
        'Ukraine', 'United Kingdom', 'Vatican City');

    $dataSource = new \Kendo\Data\DataSource();
    $dataSource->data($countries);

    $autoComplete = new \Kendo\UI\AutoComplete('countries');

    $autoComplete->dataSource($dataSource)
                 ->filter('startswith')
                 ->placeholder('Select country...')
                 ->separator(', ');

    echo $autoComplete->render();
	echo 'here';
    ?>
    <div class="demo-hint">Start typing the name of an European country</div>
</div>

<style>
    .k-autocomplete {
        width: 100%;
    }
</style>
<?php require_once '../include/footer.php'; ?>
