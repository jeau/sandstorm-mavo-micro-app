<?php
$edit_mode = isset($_GET['edit']) ;
$page = (isset($_GET['page'])) ? preg_replace('/[^A-Za-z0-9-]+/', '-', $_GET['page']) : 'home' ;
$content = 'pages/' . $page . '.txt' ;
$data = 'pages/' . $page . '.json' ;

if (! file_exists($content)) {
	$content_file = fopen($content, "w") or die("Unable to create content file!");
	fclose($content_file);
	$data_file = fopen($data, "w") or die("Unable to create data file!");
	fclose($data_file);
	$edit_mode = true ;
}

if ($edit_mode) {
	$attributes = 'mv-format="text" mv-storage="' . $content . '"';
	$current_link = '<a href="?page=' . $page . '">back to page <i>' . $page . '</i></a>';
	$html = '<h1>Edit ' . $page . '</h1>' . "\n'";
	$html .= '<textarea property="code" rows="40" cols="80" ></textarea>';
} else {
	$attributes = 'mv-storage="' . $data . '"';
	$current_link = '<a href="?page=' . $page . '&edit=ok">edit this page</a>';
	$html = file_get_contents($content);
}

$menu = "";
foreach (scandir("pages") as $file) {
		$path = pathinfo($file) ;
		if ($path['extension'] == "txt") $menu .= '<a href="?page=' . $path['filename'] . '">' . $path['filename'] . '</a> - ';
}
?>

<html lang="en">
	<head>
		<meta charset="UTF-8">
		<title>Basic sandstorm mavo app</title>
		<script src="https://get.mavo.io/mavo.js"></script>
		<script src="mavo-php.js"></script>
		<link rel="stylesheet" href="https://get.mavo.io/mavo.css">
	</head>
	<body>
		<main mv-app="grain" mv-storage-type="php" <?php echo $attributes; ?> container>
			<?php echo $html ; ?>
		</main>
		<p><?php echo $current_link . " — pages : " . $menu ;?></p>
    <form action="/index.php">
  		<p>Create a new page : <input type="text" name="page" ></p>
    </form>
	</body>
</html>
