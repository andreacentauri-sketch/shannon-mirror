<?php
header('Content-Type: application/json');
$base = realpath(__DIR__ . '/..'); // /shannon
$rel = isset($_GET['file']) ? $_GET['file'] : '';
$path = realpath($base . DIRECTORY_SEPARATOR . $rel);
if ($rel === '' || $path === false || strpos($path, $base) !== 0 || !file_exists($path)) {
  echo json_encode(["verified"=>false, "error"=>"file_not_found_or_invalid"]);
  exit;
}
$registry_path = realpath($base . '/core_identity/checksum_registry.json');
if ($registry_path === false || !file_exists($registry_path)) {
  echo json_encode(["verified"=>false, "error"=>"registry_missing"]);
  exit;
}
$registry = json_decode(file_get_contents($registry_path), true);
if (!isset($registry['registry'])) {
  echo json_encode(["verified"=>false, "error"=>"registry_invalid"]);
  exit;
}
$contents = file_get_contents($path);
$hash = hash('sha256', $contents);
$rel_norm = str_replace('\\', '/', str_replace($base . '/', '', $path));
$found = null;
foreach ($registry['registry'] as $entry) {
  if ($entry['file'] === $rel_norm) { $found = $entry; break; }
}
if ($found && strtolower($found['checksum']) === strtolower($hash)) {
  echo json_encode(["verified"=>true, "checksum_match"=>true, "checksum"=>$hash, "file"=>$rel_norm]);
} else if ($found) {
  echo json_encode(["verified"=>false, "checksum_match"=>false, "expected"=>$found['checksum'], "actual"=>$hash, "file"=>$rel_norm]);
} else {
  echo json_encode(["verified"=>false, "error"=>"file_not_in_registry", "checksum"=>$hash, "file"=>$rel_norm]);
}
?>