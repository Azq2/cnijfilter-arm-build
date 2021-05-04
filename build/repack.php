<?php
$mode = $argv[1];

echo "BUILD MODE: $mode\n";

$tmp_dir = __DIR__."/build/tmp";
$result_dir = __DIR__."/build/result/$mode";

if (file_exists($tmp_dir))
	cmd("rm", "-rf", $tmp_dir);

if (file_exists($result_dir))
	cmd("rm", "-rf", $result_dir);

$analyzer = new LibsAnalyzer();

$dirs = [];

foreach (glob(__DIR__."/build/*.deb") as $file) {
	if (preg_match("/dbgsym/", $file))
		continue;
	
	if (!preg_match("/^cnijfilter-([\w\d_-]+)_/", basename($file), $m))
		continue;
	
	$name = $m[1];
	
	$dirs[] = $name;
	
	echo "$name\n";
	echo "  -> unpack deb...\n";
	cmd("mkdir", "-p", "$tmp_dir/$name");
	cmd("dpkg-deb", "-R", $file, "$tmp_dir/$name");
	
	if ($mode == "light") {
		echo "  -> remove unnecessary files...\n";
		
		// Remove backends
		cmd("rm", "-rf", "$tmp_dir/$name/usr/lib/cups/backend");
		
		if ($name != "common") {
			// Remove all docs
			// All licenses have copies on common package
			cmd("rm", "-rf", "$tmp_dir/$name/usr/share/doc");
		}
		
		// Remove some misc unnecessary files
		cmd("rm", "-rf", "$tmp_dir/$name/usr/share/cnijlgmon2");
		cmd("rm", "-rf", "$tmp_dir/$name/usr/share/applications");
		
		// Remove chnagelogs
		cmd("find", "$tmp_dir/$name", "-iname", "changelog*", "-delete");
		
		// Remove some tools
		// We only need bjfilter* and cif*
		cmd(
			"find", "$tmp_dir/$name", "(",
				"-iname", "lgmon*", "-o",
				"-iname", "cnij*", "-o",
				"-iname", "cngp*", "-o",
				"-iname", "libcnnet*", "-o",
				"-iname", "canon-maintenance",
			")", "-delete"
		);
	}
	
	// Remove empty dirs
	cmd("find", "$tmp_dir/$name", "-type", "d", "-empty", "-delete");
	
	echo "  -> patch elfs & analyze libs...\n";
	patchElfs($analyzer, "$tmp_dir/$name");
	
	echo "  -> patch debian/control...\n";
	patchDebianControl("$tmp_dir/$name/DEBIAN/control");
}

echo "Copy x86 libs...\n";
cmd("mkdir", "-p", $tmp_dir."/common/usr/lib/bjlib/x86");
foreach ($analyzer->getLibs() as $lib)
	cmd("cp", $lib, $tmp_dir."/common/usr/lib/bjlib/x86");

echo "Build debs...\n";
cmd("mkdir", "-p", $result_dir);
foreach ($dirs as $name) {
	echo "build: $name\n";
	cmd("fakeroot", "dpkg-deb", "-b", "$tmp_dir/$name", "$result_dir/cnijfilter-$name.deb");
}

class LibsAnalyzer {
	public $paths = [];
	public $libs = [];
	
	public function analyze($file) {
		if (isset($this->paths[$file]))
			return;
		
		$this->paths[$file] = true;
		
		$src = shell_exec("ldd ".escapeshellarg($file));
		preg_match_all("/([\/][\S]+)/", $src, $m);
		
		foreach ($m[1] as $lib) {
			if (file_exists($lib)) {
				$this->libs[$lib] = true;
				$this->analyze($lib);
			}
		}
	}
	
	public function getLibs() {
		return array_keys($this->libs);
	}
}

function patchDebianControl($path) {
	$content = file_get_contents($path);
	$content = preg_replace_callback("/Depends: (.*?)\n/si", function ($m) {
		if (preg_match("/cnijfilter-common/", $m[1])) {
			return "Depends: cnijfilter-common\n";
		} else {
			return "Depends: qemu-user, qemu-user-binfmt\n";
		}
	}, $content);
	$content = str_replace("Architecture: i386", "Architecture: all", $content);
	file_put_contents($path, $content);
}

function patchElfs($analyzer, $path) {
	$directory = new \RecursiveDirectoryIterator($path);
	$iterator = new \RecursiveIteratorIterator($directory);
	
	foreach ($iterator as $info) {
		if ($info->isFile() && isElf($info->getPathname())) {
			$analyzer->analyze($info->getPathname());
			
			if (!preg_match("/\.so/", $info->getPathname()))
				cmd("patchelf", "--set-interpreter", "/usr/lib/bjlib/x86/ld-linux.so.2", "--set-rpath", "/usr/lib/bjlib/x86/", "--force-rpath", $info->getPathname());
		}
	}
}

function isElf($file) {
	$fp = fopen($file, "r");
	$magic = fread($fp, 4);
	fclose($fp);
	return $magic === "\x7f\x45\x4c\x46";
}

function cmd() {
	$args = array_map(function ($v) { return escapeshellarg($v); }, func_get_args());
	$cmd = implode(" ", $args);
	
	$ret = system($cmd);
	if ($ret != 0)
		die("Command failed, ret=$ret");
}
