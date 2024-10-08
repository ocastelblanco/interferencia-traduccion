<?php
const ORIGEN = "Z:" . DIRECTORY_SEPARATOR . "CPCAN" . DIRECTORY_SEPARATOR . "global_assets" . DIRECTORY_SEPARATOR . "resource" . DIRECTORY_SEPARATOR;
//const ORIGEN = __DIR__ . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR;
const DESTINO = __DIR__ . DIRECTORY_SEPARATOR . "recursosCO" . DIRECTORY_SEPARATOR;
const NOMBRE_ZIP = "_resource_content.zip";
if (!file_exists(DESTINO)) mkdir(DESTINO, 0777, true);
$gestor = fopen("listaCO.csv", "r");
$encabezados = fgetcsv($gestor, null, ";");
$listaArchivos = [];
while (($fila = fgetcsv($gestor, null, ";")) != FALSE) $listaArchivos[] = $fila[0];
fclose($gestor);
print "Inicio la extracción de " . count($listaArchivos) . " en total." . PHP_EOL;
foreach ($listaArchivos as $num => $id) {
  print "Extrayendo recurso $id: " . ($num + 1) . "/" . count($listaArchivos) . PHP_EOL;
  $origen = ORIGEN . getRutaRecurso($id);
  if (!file_exists($origen)) {
    print "No existe $origen, seguiré con el siguiente recurso." . PHP_EOL;
  } else {
    $zip = new ZipArchive;
    $zip->open($origen);
    $interfaz = $zip->getFromName("data/interfaz.json");
    $datos = $zip->getFromName("data/datos.json");
    $zip->close();
    $jsonContent = "{\n\"datos\": $datos ,\n\"interfaz\": $interfaz\n}";
    $csvGestor = fopen(DESTINO . "$id.csv", "w");
    fwrite($csvGestor, "\xEF\xBB\xBF");
    fputcsv($csvGestor, jsonToCsv($jsonContent, $id, true), ";");
    fputcsv($csvGestor, jsonToCsv($jsonContent, $id), ";");
    fclose($csvGestor);
  }
}
// Funciones
function getRutaRecurso(string $id, bool $conZIP = true)
{
  return implode(DIRECTORY_SEPARATOR, str_split(str_pad($id, 9, "0", STR_PAD_LEFT), 3)) . DIRECTORY_SEPARATOR .
    ($conZIP ? NOMBRE_ZIP : "");
}
function jsonToCsv(string $jsonContent, string $id, bool $headers = false)
{
  $data = json_decode($jsonContent, true);
  return $headers ? array_merge(['id_asset_ES', 'id_asset'], array_keys(flattenArray($data))) : array_merge([$id, ''], array_values(flattenArray($data)));
}
function flattenArray(array $array, string $prefix = '')
{
  $result = [];
  foreach ($array as $key => $value) {
    $fullKey = $prefix === '' ? $key : $prefix . '.' . $key;
    if (is_array($value)) {
      $result = array_merge($result, flattenArray($value, $fullKey));
    } else {
      $result[$fullKey] = $value;
    }
  }
  return $result;
}
function str_putcsv(array $input, string $delimiter = ',', string $enclosure = '"')
{
  $fp = fopen('php://temp', 'r+');
  fputcsv($fp, $input, $delimiter, $enclosure);
  rewind($fp);
  $data = fread($fp, 1048576);
  fclose($fp);
  return rtrim($data, "\n");
}
