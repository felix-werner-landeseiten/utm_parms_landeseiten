<?php
/**
 * Builds dist/utm-parameter-forwarder.zip, ready for "Plugins > Add New > Upload Plugin".
 *
 * Usage: php bin/build.php [expected-version]
 * If expected-version is given (CI passes the tag), the build fails when the
 * plugin header, the UTMPF_VERSION constant and readme.txt do not all match it.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 1 );
}

$root   = dirname( __DIR__ );
$slug   = 'utm-parameter-forwarder';
$src    = $root . '/' . $slug;
$main   = file_get_contents( $src . '/' . $slug . '.php' );
$readme = file_get_contents( $src . '/readme.txt' );

preg_match( '/^\s*\*\s*Version:\s*(\S+)/m', $main, $header );
preg_match( "/define\(\s*'UTMPF_VERSION',\s*'([^']+)'/", $main, $constant );
preg_match( '/^Stable tag:\s*(\S+)/m', $readme, $stable );

$versions = array(
	'plugin header'          => isset( $header[1] ) ? $header[1] : '',
	'UTMPF_VERSION'          => isset( $constant[1] ) ? $constant[1] : '',
	'readme.txt Stable tag'  => isset( $stable[1] ) ? $stable[1] : '',
);
if ( isset( $argv[1] ) && '' !== $argv[1] ) {
	$versions['expected (tag)'] = $argv[1];
}

if ( count( array_unique( $versions ) ) !== 1 || '' === reset( $versions ) ) {
	fwrite( STDERR, "Version mismatch:\n" );
	foreach ( $versions as $where => $v ) {
		fwrite( STDERR, sprintf( "  %-24s %s\n", $where, $v ) );
	}
	exit( 1 );
}
$version = reset( $versions );

$dist = $root . '/dist';
if ( ! is_dir( $dist ) ) {
	mkdir( $dist, 0755, true );
}
$target = $dist . '/' . $slug . '.zip';
if ( file_exists( $target ) ) {
	unlink( $target );
}

$zip = new ZipArchive();
if ( true !== $zip->open( $target, ZipArchive::CREATE ) ) {
	fwrite( STDERR, "Cannot create $target\n" );
	exit( 1 );
}

$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $src, FilesystemIterator::SKIP_DOTS ) );
$count = 0;
foreach ( $files as $file ) {
	if ( ! $file->isFile() ) {
		continue;
	}
	$relative = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $src ) + 1 ) );
	// Never ship dot-files (.DS_Store, editor configs, etc.).
	if ( preg_match( '#(^|/)\.#', $relative ) ) {
		continue;
	}
	// Forward slashes so the archive extracts correctly on Linux servers.
	$zip->addFile( $file->getPathname(), $slug . '/' . $relative );
	$count++;
}
$zip->close();

echo "Built $target (version $version, $count files)\n";
