<?php
/**
 * Prints the CHANGELOG.md section for one version (used as GitHub release notes).
 *
 * Usage: php bin/changelog.php 1.1.0
 */

if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) {
	exit( 1 );
}

$lines   = file( dirname( __DIR__ ) . '/CHANGELOG.md', FILE_IGNORE_NEW_LINES );
$heading = '## [' . $argv[1] . ']';
$out     = array();
$inside  = false;

foreach ( $lines as $line ) {
	if ( 0 === strpos( $line, '## ' ) ) {
		if ( $inside ) {
			break;
		}
		$inside = 0 === strpos( $line, $heading );
		continue;
	}
	if ( $inside ) {
		$out[] = $line;
	}
}

echo trim( implode( "\n", $out ) ) ?: 'Release ' . $argv[1];
echo "\n";
