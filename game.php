<?php
namespace Game;

include 'src/Conway.php';
include 'src/Matrix.php';

$options = [];

if (isset($argv[1])) {
    parse_str($argv[1], $options);
}

$game = new Conway($options);
$game->start();

print "\nGame Over!\n\n";
?>