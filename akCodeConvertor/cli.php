#!/usr/bin/php
<?

/*
 * This file is part of the akLib package.
 * (c) 2010 Azat Khuzhin <dohardgopro@gmail.com>
 *
 * For the full copyright and license information, please view http://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Run akCodeConvertor from CLI
 * @example "cli.php --exclude a_a a.php b.php"
 * 
 * @author Azat Khuzhin <dohardgopro@gmail.com>
 * @package akLib
 * @licence GPLv2
 */

require_once __DIR__ . '/../main.php';
require_once __DIR__ . '/akCodeConvertor.class.php';

if (mb_strtolower(PHP_SAPI) != 'cli') throw new akException('Must run from CLI (Comand Line Interface)');

$help = <<<EOL
	Usage:
	cli.php [--exclude] [--fexclude] [--cexclude] [--vexclude] [--prefix] file1[ file2[...]]
	
	Example:
	cli.php --exclude a_a a.php b.php
	
	Options:
	--exclude file[,file] - Not convert this files
	--fexclude function/method_name[,function_name] - Not convert this function/method
	--cexclude class_name[,function_name] - Not convert this class
	--vexclude var_name[,function_name] - Not convert this vars
	--case_sensitive 1|0|true|false|yes|not - case sensetive or not (default: not)
	--prefix - prefix for new files (if not prefix - rewrite files)
	
	--help see this page

EOL;
$excludeFiles = $excludeFunctions = $excludeClasses = $excludeVars = array();
$caseSensitive = false;
$prefix = null;
$scriptName = array_shift($argv);

if ((count($argv) == 1 && $argv[0] == '--help') || (count($argv) == 0)) {
	echo $help;
} else {
	// get options
	for ($i = 0; $i < count($argv); $i++) {
		// exclude files
		if ($argv[$i] == '--exclude') {
			if (isset($argv[$i+1])) {
				$excludeFiles = array_merge($excludeFiles, array_map('trim', preg_split('@[,]@Uis', $argv[$i+1])));
				unset($argv[$i], $argv[$i+1]);
				$i++;
			} else {
				throw new akException('--exclude option require an argument');
			}
		}
		// exclude functions
		elseif ($argv[$i] == '--fexclude') {
			if (isset($argv[$i+1])) {
				$excludeFunctions = array_merge($excludeFunctions, array_map('trim', preg_split('@[,]@Uis', $argv[$i+1])));
				unset($argv[$i], $argv[$i+1]);
				$i++;
			} else {
				throw new akException('--fexclude option require an argument');
			}
		}
		// exclude classes
		elseif ($argv[$i] == '--cexclude') {
			if (isset($argv[$i+1])) {
				$excludeClasses = array_merge($excludeClasses, array_map('trim', preg_split('@[,]@Uis', $argv[$i+1])));
				unset($argv[$i], $argv[$i+1]);
				$i++;
			} else {
				throw new akException('--cexclude option require an argument');
			}
		}
		// exclude vars
		elseif ($argv[$i] == '--vexclude') {
			if (isset($argv[$i+1])) {
				$excludeVars = array_merge($excludeVars, array_map('trim', preg_split('@[,]@Uis', $argv[$i+1])));
				unset($argv[$i], $argv[$i+1]);
				$i++;
			} else {
				throw new akException('--vexclude option require an argument');
			}
		}
		// case sensitive
		elseif ($argv[$i] == '--case_sensitive') {
			if (isset($argv[$i+1])) {
				$caseSensitive = (in_array($argv[$i+1], array('1', 'true', 'yes')) ? true : false);
				unset($argv[$i], $argv[$i+1]);
				$i++;
			} else {
				throw new akException('--case_sensitive option require an argument');
			}
		}
		// prefix
		elseif ($argv[$i] == '--prefix') {
			if (isset($argv[$i+1])) {
				$prefix = $argv[$i+1];
				unset($argv[$i], $argv[$i+1]);
				$i++;
			} else {
				throw new akException('--prefix option require an argument');
			}
		}
	}
	// reset index
	$argv = array_values($argv);
	// exclude files
	for ($i = 0; $i < count($argv); $i++) {
		if (in_array($argv[$i], $excludeFiles)) {
			unset($argv[$i]);
		}
	}
	
	// scalar options
	printf('Case sensetive: %u' . "\n", $caseSensitive);
	if ($prefix) {
		printf('Prefix: %s' . "\n", $prefix);
	} else {
		echo 'Changes will be writen to existed files!' . "\n";
	}
	// non-scalar options
	if ($excludeFunctions) {
		echo 'Exclude next functions: ' . "\n";
		var_dump($excludeFunctions);
	}
	if ($excludeClasses) {
		echo 'Exclude next classes: ' . "\n";
		var_dump($excludeClasses);
	}
	if ($excludeVars) {
		echo 'Exclude next vars: ' . "\n";
		var_dump($excludeVars);
	}
	if ($excludeFiles) {
		echo 'Exclude files: ' . "\n";
		var_dump($excludeFiles);
	}
	// files to convert
	echo 'Files: ' . "\n";
	var_dump($argv);
	
	$converter = akCodeConvertor::getInstance($argv, $excludeFunctions, $excludeClasses, $excludeVars, $caseSensitive);
	$converter->prefixForNewFiles = $prefix;
	$status = $converter->run();
	if ($status) {
		echo 'All files are parsed' . "\n";
	}
}
