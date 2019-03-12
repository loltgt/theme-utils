<?php
$bases = ['/wp-content/themes/theme', '/wp-content/themes/codename'];
$dirs = ['', 'inc', 'page-templates', 'template-parts'];
$comms = [];

foreach ($bases as $_base) {
	$base = realpath("..{$_base}");

	foreach ($dirs as $dir) {
		if (! is_dir("{$base}/{$dir}"))
			continue;

		$diri = scandir("{$base}/{$dir}");

		foreach ($diri as $file) {
			if ($file[0] == '.' || strpos($file, '.php') == false)
				continue;

			$filename = ($dir ? $dir . '/' : '') . $file;
			$source = file_get_contents($base . '/' . $filename);
			$filename = $_base . '/' . $filename;

			foreach (token_get_all($source) as $token) {
				if (isset($token[0]) && is_int($token[0]) && strpos(token_name($token[0]), '_COMMENT') !== false)
					$comms[$filename][] = $token[1];
			}
		}
	}
}

$printer = "<?php\n\n\n";

foreach ($comms as $filename => $comments) {
	$printer .= "# {$filename}\n\n";

	foreach ($comments as $comment) {
		$comment = preg_replace('/^[\t]+/m', '', $comment);
		$printer .= "{$comment}\n\n";
	}

	$printer .= "\n\n\n";
}

file_put_contents('comms.php', $printer);