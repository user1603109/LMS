<?php
if (PHP_SAPI !== 'cli') {
	echo "Run from CLI: php scripts/gen_password_hash.php <password>\n";
	exit(1);
}
$pwd = $argv[1] ?? null;
if (!$pwd) {
	fwrite(STDERR, "Usage: php scripts/gen_password_hash.php <password>\n");
	exit(1);
}
$hash = password_hash($pwd, PASSWORD_DEFAULT);
echo $hash, PHP_EOL;