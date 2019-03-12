<?php

$paths = ['theme', 'theme-codename'];
$dirs = ['inc', '', 'page-templates', 'template-parts'];

$priorities = [
	0 => 'theme::functions',
	5 => 'codename::functions',
	10 => 'theme:inc:class-theme-setup',
	20 => 'theme:inc:class-theme-functions',
	30 => 'theme:inc:class-theme-template',
	40 => 'theme:inc:class-theme-layer',
	41 => 'theme:inc:class-theme-layer-factory',
	42 => 'theme:inc:class-theme-layer-cmb2',
	43 => 'theme:inc:class-theme-layer-cfs',
	44 => 'theme:inc:class-theme-layer-acf',
	50 => 'theme:inc:class-theme-customizer',
	60 => 'theme:inc:class-theme-shop-wc',
	70 => 'theme:inc:class-widget-recent-posts',
	80 => 'theme:inc:class-widget-recent-comments',
	90 => 'theme:inc:class-theme-admin',
	100 => 'theme:inc:class-theme-options',
	110 => 'theme:inc:template-functions',
	120 => 'theme:inc:template-tags'
];


$names = [
	'abstract' => 'Classes',
	'class' => 'Classes',
	'final' => 'Classes',
	'interface' => 'Functions',
	'function' => 'Functions',
	'apply_filters' => 'Filters',
	'do_action' => 'Hooks',
	'protected' => '',
	'private' => '',
	'public' => '',
	'const' => '',
	'var' => ''
];




if (! function_exists('array_key_last')) {
	function array_key_last($array = array()) {
		end($array);
		$_last = key($array);
		reset($array);

		return $_last;
	}
}

function dircm($file, $dir, $path) {
	global $priorities;

	if ($file[0] == '.' || strpos($file, '.php') == false)
		return false;

	$name = substr($file, 0, -4);
	$name = slugify($name);
	$name = $path . ':' . $dir . ':' . $name;

	$priority = array_search($name, $priorities, true);

	if ($priority === false) {
		$priority = array_key_last($priorities);
		$priority++;
		$priorities[$priority] = $name;
	}

	$dir && $dir .= '/';

	return [$dir . $file, $priority];

}


function fndlci($source, $token, $token_name) {
	$index = explode(PHP_EOL, $token[1]);
	$index = (count($index) - 1) + $token[2];

	$line = '';
	$comment = '';

	if ($token_name === 'T_DOC_COMMENT') {
		(isset($source[$index])) && $line = $source[$index];
		(isset($token[1])) && $comment = $token[1];

		for ($s = 1; $s < 3; $s++) {
			$l = $index + $s;

			if (! isset($source[$l]))
				continue;

			$line .= "\n" . $source[$l];
		}
	}

	if ($token_name == 'T_COMMENT') {
		$l = $index + 2;
		$index = $index + 1;

		(isset($source[$l])) && $line = $source[$l];
		(isset($source[$index])) && $comment = $source[$index];

		for ($s = 1; $s < 3; $s++) {
			$c = $index + $s;

			if (! isset($source[$c])) continue;

			if (strpos($line, '// ') !== false && strpos($source[$c], '// ') === false)
				$line = $source[$c];

			if (strpos($source[$c], '// ') !== false)
				$comment .= "\n" . $source[$c];
		}
	}

	return ['line' => $line, 'comment' => $comment, 'index' => $index];
}


function sitc($line, $token_name) {
	global $names;

	$r = false;
	$mline = explode("\n", $line);

	foreach ($mline as $line) {
		$t = preg_replace('/([\t]+|[\']+|["]+|,|\{|\}|\(|\))/', '', $line);
		$t = explode(' ', $t);

		if (isset($t[3]) && array_key_exists($t[2], $names))
			$r = [$t[3], $t[2], $t[1], $t[0]];
		elseif (isset($t[2]) && array_key_exists($t[2], $names))
			$r = [$t[1], $t[2], $t[0]];
		elseif (isset($t[1]) && array_key_exists($t[1], $names))
			$r = [$t[2], $t[1], $t[0]];
		elseif (isset($t[0]) && array_key_exists($t[0], $names))
			$r = [$t[1], $t[0]];

		if ($r) break;
	}

	return $r;
}


function clseis($line) {
	$eis = [];

	if ($s = strpos($line, 'extends')) {
		$extends = explode('extends ', substr($line, $s, - (strlen($line) - strpos($line, ' {'))));
		$eis['extends'] = array_filter($extends);
	}

	if ($s = strpos($line, 'implements')) {
		$implements = explode('implements ', substr($line, $s, - (strlen($line) - strpos($line, ' {'))));
		$eis['implements'] = array_filter($implements);
	}

	if (empty($eis)) $eis = null;

	return $eis;
}


function pcbbl($line, $args = null) {
	$lc = '';

	if ($line[0] === '@')
		$lc = substr($line, 1, (($s = strpos($line, ' ')) ? ($s - 1) : strlen($line)));
	else if ($line === '...')
		$lc = 'type';

	$line = preg_replace('/([\t]+|[\']+|\{|\})/', '', $line);

	if ($lc) {
		$line = explode(' ', $line);
		$line = array_map('trim', $line);
	}

	$r = null;


	switch ($lc) {
		case 'link':
			$r = 'link to: [' . $line[1] . '](' . $line[1] . ')';
		break;

		case 'see':
			$r = 'code refer to: ' . $line[1];
		break;

		case 'license':
			$r = 'license: ' . $line[1];
		break;

		case 'access':
			$r = $line[1];
		break;

		case 'global':
		case 'type':
		case 'param':
			$lc = ($lc !== 'global' ? 'params' : 'globals');

			if (! empty($line[1]))
				$r = "<" . str_replace("|", "> or <", $line[1]) . ">";
			else
				$r = "...";

			if (! empty($line[2])) {
				$r .= " " . $line[2];

				if ($lc !== 'globals' && ! empty($args[$line[2]]))
					$r .= "   default: " . $args[$line[2]];
			}

			if (! empty($line[4])) {
				$r .= "   reference: " . $line[4];
			}
		break;

		case 'return':
			$r = "<" . str_replace("|", "> or <", $line[1]) . ">";

			if (! empty($line[2]))
				$r .= " " . $line[2];

			if (! empty($line[3]))
				$r .= "   default: " . str_replace("|", " or ", $line[3]);
		break;

		case 'static':
			$lc = 'type';
			$r = 'static';
		break;

		case 'since':
			$r = $line[1];
		break;

		default:
			$r = $line;
	}

	return [$lc, $r];
}


function pcom($comment, $node = null) {
	$comm = str_replace(["/**", " */", " * ", " *", "// ", "\t"], "", $comment);
	$comm = explode(PHP_EOL, $comm);

	$args = null;

	if ($node) {
		$args = substr($node, (strpos($node, '(') + 2), (strrpos($node, ')') - strpos($node, '(') - 3));
		
		if ($args) {
			$_args = explode(', ', $args);
			$args = [];

			foreach ($_args as $arg) {
				if (strpos($arg, '=')) {
					$arg = preg_split('/\s=\s/', $arg);
					if (! isset($arg[1])) continue;
					$args[$arg[0]] = $arg[1];
				} else {
					$args[$arg] = '';
				}
			}
		}
	}

	$doc = ['comment' => ''];

	$depth = "";

	foreach ($comm as $i => $line) {
		if (empty($line)) continue;

		$bbl = pcbbl($line, $args);

		($depth && is_int(strpos($line, '}'))) && $depth = "";

		if ($bbl[0] == 'params' || $bbl[0] == 'globals') {
			$doc[$bbl[0]][$i] = $depth . $bbl[1];

			(! $depth && is_int(strpos($line, '{'))) && $depth = "\t";
		} elseif ($bbl[0]) {
			$doc[$bbl[0]] = $bbl[1];
		} else {
			$doc['comment'] .= $bbl[1];
		}
	}

	if (empty($doc['comment'])) {
		unset($doc['comment']);
	} elseif (strstr($doc['comment'], '//TODO')) {
		$doc['comment'] = explode('//TODO', $doc['comment']);
		$doc['todo'] = trim($doc['comment'][1]);
		$doc['comment'] = $doc['comment'][0];
	}

	return $doc;
}


function slugify($text) {
	$text = preg_replace('/[^A-Za-z0-9_-]+/', '-', $text);
	$text = preg_replace('/^-/', '', $text);

	return $text;
}





$diri = [];

foreach ($paths as $path) {
	$base = realpath(__DIR__ . '/../wp-content/themes/' . $path);

	foreach ($dirs as $dir) {
		$dirc = $base . '/' . $dir;

		if (! is_dir($dirc)) continue;

		foreach (scandir($dirc) as $file)
			(list($file, $priority) = dircm($file, $dir, $path)) && $diri[$priority] = [$path, $file];
	}
}

ksort($diri);


$docs = [];

foreach ($diri as $brs) {
	$path = $brs[0];
	$filename = $brs[1];

	$file = realpath(__DIR__ . '/../wp-content/themes/' . $path . '/' . $filename);

	$source = file_get_contents($file);

	$tokens = token_get_all($source);
	$source = explode(PHP_EOL, $source);

	$classname = null;


	foreach ($tokens as $token) {

		if (! (isset($token[0]) && is_int($token[0]))) continue;

		$token_name = token_name($token[0]);

		if ($token_name !== 'T_DOC_COMMENT' && $token_name !== 'T_COMMENT') continue;


		extract(fndlci($source, $token, $token_name));


		if (($f = sitc($line, $token_name)) === false) continue;


		$doc = pcom($comment, $source[$index]);

		$category = $names[$f[1]];

		$three = $category;
		$subthree = $filename;
		$i = slugify($f[0]);


		if ($category == 'Classes' || $category == 'Functions') {
			if ($category === 'Classes') {
				$classname = $i;
				$subthree = $classname;

				($eis = clseis($source[$index])) && $doc['eis'] = $eis;
			}

			if ($classname && isset($source[$index][0]) && $source[$index][0] == "\t") {
				$three = 'Classes';
				$subthree = $classname;
			}
		}

		if ($category == 'Hooks' || $category == 'Filters') {
			if ($three === 'Classes') $classname = null;
			if (! strstr($f[0], 'theme_')) continue;
		}

		if ($classname && ! $category) {
			$three = 'Classes';
			$subthree = $i = $classname;
			$param = "";

			(isset($f[2])) && $param .= "{$f[2]} ";
			(isset($f[1])) && $param .= "{$f[1]} ";
			(isset($doc['params'])) && $param .= implode('', $doc['params']);
			(isset($doc['comment'])) && $param .= "\t// {$doc['comment']}";

			$docs[$three][$subthree][$i]['params'][$f[0]] = $param;

			continue;
		}


		if (! isset($docs[$three][$subthree][$i]))
			$docs[$three][$subthree][$i] = $doc;

		$file_url = "https://github.com/loltgt/{$path}/blob/master/{$filename}";

		$l = $index + 1;
		$line_url = "{$file_url}#L{$l}";

		$sth  = ($category === 'Classes' && isset($f[2])) ? "{$f[2]} {$f[1]} " : "{$f[1]} ";
		$sth  = ($category === 'Classes' || $category === 'Functions') ? $sth : "";
		$sth .= "**{$f[0]}**";


		$docs[$three][$subthree][$i]['name'] = $f[0];
		$docs[$three][$subthree][$i]['sth'] = $sth;
		$docs[$three][$subthree][$i]['category'] = $category;
		$docs[$three][$subthree][$i]['file'] = $filename;
		$docs[$three][$subthree][$i]['classname'] = $classname ? "\\theme\\{$classname}" : "";
		$docs[$three][$subthree][$i]['position'][] = "- [{$filename}]({$file_url})   line: [{$l}]({$line_url})";

		if (count($docs[$three][$subthree][$i]['position']) > 1)
			$docs[$three][$subthree][$i]['classname'] = "";

	}
}



$base = realpath('./docs');

$menu = [];

foreach ($docs as $three => $subthree) {

	$node = $name = $three;
	$file = $base . '/' . $node . '.md';

	$print_file = false;

	$body = "";
	$header = "";
	$list = "";

	$menu[$three][] = "### [[{$node}|{$name}]]";
	$menu[$three][] = "";


	foreach ($subthree as $doc) {

		if (! $header) {
			$header .= "\n\n";
			$header .= "# {$three}\n";
			$header .= "\n\n";
		}

		if ($three == 'Classes' || $three == 'Functions')
			$body = "";

		$overwrite = true;


		foreach ($doc as $text) {

			if ($three == 'Classes' || $three == 'Functions') {
				if ($three == 'Classes') {
					$parent = $text['classname'];
				} elseif ($three == 'Functions') {
					$parent = $text['file'];
				}

				$node = slugify($parent);

				$name = $three . '-' . $node;
				$name = str_replace(array('inc-', '-php'), '', $name);

				$file = $base . '/' . $name . '.md';


				if ($overwrite) {
					$header  = "\n\n";
					$header .= "# {$parent}\n";
					$header .= "\n\n";

					$list .= "\n## [[{$parent}|{$name}]]\n";

					$menu[$three][] = "";
					$menu[$three][] = "## [[{$parent}|{$name}]]";

					$overwrite = false;
				}

				$print_file = true;
			}

			if ($text['category'] !== 'Classes') {
				$node = $text['name'];
				$anchor = urlencode($text['name']);

				$body .= "## {$text['name']}\n\n";

				if ($three == 'Classes' || $three == 'Functions')
					$list .= "- [[{$node}|{$name}#{$anchor}]]\n";

				$menu[$three][] = "* [[{$node}|{$name}#{$anchor}]]";
			}


			$body .= "{$text['sth']}\n\n";

			if (isset($text['eis'])) {
				if (isset($text['eis']['extends'])) 
					$body .= "extends: *" . implode(", ", $text['eis']['extends']) . "*\n\n";

				if (isset($text['eis']['implements'])) 
					$body .= "implements: *" . implode(", ", $text['eis']['implements']) . "*\n\n";
			}

			if (isset($text['comment']))
				$body .= "#### {$text['comment']}\n\n";

			if (isset($text['todo'])) {
				$text['todo'] = "##### TODO: *" . $text['todo'] . "*";
				$body .= "{$text['todo']}\n\n";
			}

			if (isset($text['link']))
				$body .= "{$text['link']}\n\n";

			if (isset($text['see']))
				$body .= "{$text['see']}\n\n";

			if (isset($text['license']))
				$body .= "{$text['license']}\n\n";

			if ($text['category'] === 'Functions') {
				if (isset($text['type'])) {
					$text['type'] = "type: *" . $text['type'] . "*";
					$body .= "{$text['type']}\n\n";
				}

				if (isset($text['access'])) {
					$text['access'] = "access: *" . (isset($text['access']) ? $text['access'] : "public") . "*";
					$body .= "{$text['access']}\n\n";
				}
			}

			if (isset($text['globals'])) {
				$text['globals'] = "globals: \n```php\n" . implode("\n", $text['globals']) . "\n```\n";
				$body .= "{$text['globals']}\n\n";
			}

			if (isset($text['params'])) {
				$params_label = ($text['category'] === 'Classes' ? "parameters" : "arguments");
				$text['params'] = "{$params_label}: \n```php\n" . implode("\n", $text['params']) . "\n```\n";
				$body .= "{$text['params']}\n\n";
			}

			if (isset($text['return'])) {
				$text['return'] = "returns: \n```php\n" . $text['return'] . "\n```\n";
				$body .= "{$text['return']}\n\n";
			}

			if (isset($text['position'])) {
				$text['position'] = "position: \n" . implode("\n", $text['position']);
				$body .= "{$text['position']}\n\n";
			}

			$body .= " \n\n";

		}


		if ($print_file && $body) file_put_contents($file, $header . $body);


	}


	if ($list) {
		$node = $name = $three;
		$file = $base . '/' . $node . '.md';

		$header = "";
		$body = $list;
	}

	if ($body) file_put_contents($file, $header . $body);


}



$text  = "\n";
$text .= implode("\n", $menu['Classes']) . "\n\n\n";
$text .= implode("\n", $menu['Functions']) . "\n\n\n";
$text .= implode("\n", $menu['Hooks']) . "\n\n\n";
$text .= implode("\n", $menu['Filters']);
$text .= "\n";

$file = $base . '/_Sidebar.md';
file_put_contents($file, $text);

$file = $base . '/Home.md';
file_put_contents($file, $text);



file_put_contents('docs.tmp', print_r($docs, true));