<?php

list($version, $arch, $ts, $extension) = array_slice($argv, 1);

$versions = [
    "5.2" => "vc6",
    "7.0" => "vc14",
    "7.1" => "vc14",
    "7.2" => "vc15",
    "7.4" => "vc15",
    "8.0" => "vs16",
    "8.1" => "vs16",
    "8.2" => "vs16",
];
if (!array_key_exists($version, $versions)) {
    throw new Exception("Unsupported version: $version");
}
file_put_contents(getenv("GITHUB_OUTPUT"), "vs={$versions[$version]}\n", FILE_APPEND);

$ini = parse_ini_file(__DIR__ . "/pecl.ini", true, INI_SCANNER_RAW);
if (!array_key_exists($extension, $ini)) {
    throw new Exception("Unsupported extension: $extension");
}
file_put_contents(getenv("GITHUB_OUTPUT"), "config={$ini[$extension]['config']}\n", FILE_APPEND);

if (array_key_exists("libs", $ini[$extension])) {
    $libs = $ini[$extension]["libs"];
} else {
    $libs = "''";
}
file_put_contents(getenv("GITHUB_OUTPUT"), "libs={$libs}\n", FILE_APPEND);

// work around missing possibility to install PECL dependencies
if (array_key_exists("pecl_libs", $ini[$extension])) {
    $libs = $ini[$extension]["pecl_libs"];
    file_put_contents("./pecl_libs.csv", explode("\n", $libs));
}

if (array_key_exists("exts", $ini[$extension])) {
    $lines = [];
    $exts = $ini[$extension]["exts"];
    foreach (explode(",", $exts) as $ext) {
        if (array_key_exists($ext, $ini)) {
            $lines[] = "{$ext}\t{$ini[$ext]["config"]}\n";
        } else {
            throw new Exception("Unsupported dependency extension: $extension");
        }
    }
    file_put_contents("./extensions.csv", $lines);
}

// actual extension in subfolder
if (array_key_exists("subfolder", $ini[$extension])) {
    file_put_contents(getenv("GITHUB_OUTPUT"), "subfolder={$ini[$extension]['subfolder']}\n", FILE_APPEND);
}

$sxe = simplexml_load_file("./package.xml");
$sxe->registerXPathNamespace("p", $sxe->getNamespaces()[""]);
$docs = array_map(
    function ($sxe) {
        return str_replace("/", "\\", (string) $sxe["name"]);
    },
    $sxe->xpath("//p:file[@role='doc']")
);
$docs = implode(" ", $docs);
file_put_contents(getenv("GITHUB_OUTPUT"), "docs={$docs}\n", FILE_APPEND);

$builddir = "";
if ($arch === "x64") {
    $builddir .= "x64\\";
}
$builddir .= "Release";
if ($ts === "ts") {
    $builddir .= "_TS";
}
file_put_contents(getenv("GITHUB_OUTPUT"), "builddir={$builddir}\n", FILE_APPEND);
