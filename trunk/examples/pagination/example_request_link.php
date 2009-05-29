<html>
<head>
<title>Skeleton - Pagination example - basic</title>
</head>
<body>
<?php
include 'config.php';
include 'Datasource.php';
include 'A/Pagination/Request.php';

// initialize an array for testing
for ($i=0; $i<=750; ++$i) {
	$myarray[$i]['title'] = 'This is row ' . $i;
	$myarray[$i]['month'] = date ('F', time() + ($i * 60 * 60 * 24 * 30));
}
#$myarray = null;
// create a data object that has the interface needed by the Pager object
$datasource = new Datasource($myarray);

// create a request processor to set pager from GET parameters
$pager = new A_Pagination_Request($datasource);
$pager->setRangeSize(3)->process();

$url = new A_Pagination_Helper_Url();
$url->set('page', $pager->getCurrentPage());
$url->set('order_by', $pager->getOrderBy());

include 'A/Pagination/Helper/Link.php';
$link = new A_Pagination_Helper_Link($pager);

$rows = $pager->getItems();

// display the paging links ... should this go in a template?
$links = '';
$links .= $link->first('First');
$links .= $link->previous('Previous');
$links .= $link->range();
$links .= $link->last();
$links .= $link->next('Next');

echo "<div>$links</div>";

// display the data
echo '<table border="1">';
echo '<tr><th>' . $link->order('', 'Row') . '</th><th>' . $link->order('title', 'Title') . '</th><th>' . $link->order('month', 'Month') . '</th></tr>';
$n = 1;
foreach ($rows as $value) {
	echo '<tr>';
	echo '<td>' . $n++ . '.</td><td>' . $value['title'] . '</td><td>' . $value['month'] . '</td>';
	echo '</tr>';
}
echo '</table>';

echo "<div>$links</div>";

#dump($pager);
?>
<p/>
<a href="../">Return to Examples</a>
</p>

</body>
</html>