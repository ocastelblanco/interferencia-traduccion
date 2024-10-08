<?php
const BACKUP = __DIR__ . DIRECTORY_SEPARATOR . "backup" . DIRECTORY_SEPARATOR;
const SALIDA = __DIR__ . DIRECTORY_SEPARATOR . "salida" . DIRECTORY_SEPARATOR;
//const SALIDA = "Z:" . DIRECTORY_SEPARATOR . "CPCAN" . DIRECTORY_SEPARATOR . "global_assets" . DIRECTORY_SEPARATOR . "resource" . DIRECTORY_SEPARATOR;
const NOMBRE_ZIP = "_resource_content.zip";
$conZIP = isset($argv["zip"]) || isset($argv["-zip"]) || isset($argv["--zip"]);
if (!file_exists(BACKUP)) mkdir(BACKUP, 0777, true);
$iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("recursosCO"));
foreach ($iterador as $fuente) {
  $esPunto = substr(basename($fuente->getPathname()), 0, 1) == ".";
  if (!$esPunto) {
    $data = csvToJson($fuente->getPathname());
    if (!$data || !isset($data["id"])) {
      debug("ERROR: No se obtuvieron datos del CSV " . $fuente->getPathname(), 0);
      continue;
    }
    $dataInterfaz = json_encode($data["interfaz"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $dataDatos = json_encode($data["datos"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $ruta = getRutaRecurso($data["id"], false);
    debug("Extrayendo JSON del CSV " . $data["id"], -1);
    if (!$conZIP) {
      //* Sin ZIP
      $destino = SALIDA . $ruta;
      $backup = BACKUP . $ruta;
      if (!file_exists($destino)) mkdir($destino, 0777, true);
      file_put_contents($destino . "datos.json", $dataDatos);
      file_put_contents($destino . "interfaz.json", $dataInterfaz);
      debug("JSON creados correctamente", 0);
      //*/
    } else {
      //* Con ZIP
      $destino = SALIDA . $ruta;
      if (!file_exists($destino)) {
        debug("ERROR: No existe el ZIP $destino" . NOMBRE_ZIP, 0);
      } else {
        if (!file_exists($backup)) mkdir($backup, 0777, true);
        if (copy($destino . NOMBRE_ZIP, $backup . NOMBRE_ZIP)) {
          debug("Se realizó el backup correctamente", 1);
          $zip = new ZipArchive;
          $zip->open($destino . NOMBRE_ZIP);
          $zip->deleteName("data/interfaz.json");
          debug("Se eliminó interfaz.json original", 2);
          $zip->addFromString("data/interfaz.json", $dataInterfaz);
          debug("Se reemplazó interfaz.json", 2);
          $zip->deleteName("data/datos.json");
          debug("Se eliminó datos.json original", 2);
          $zip->addFromString("data/datos.json", $dataDatos);
          debug("Se reemplazó datos.json", 2);
          $zip->close();
        } else {
          debug("ERROR: No se pudo realizar la copia de seguridad", 1);
          continue;
        }
      }
      //*/
    }
  }
}
// Funciones
function csvToJson($csvFilePath)
{
  $csvFile = fopen($csvFilePath, 'r');
  $headers = fgetcsv($csvFile, null, ";");
  $row = fgetcsv($csvFile, null, ";");
  $jsonData = [];
  for ($i = 2; $i < count($headers); $i++) setNestedValue($jsonData, $headers[$i], $row[$i]);
  fclose($csvFile);
  return [
    "id" => $row[0],
    "datos" => $jsonData["datos"],
    "interfaz" => $jsonData["interfaz"],
  ];
}

function setNestedValue(&$array, $key, $value)
{
  $keys = explode('.', $key);
  while (count($keys) > 1) {
    $key = array_shift($keys);
    if (!isset($array[$key]) || !is_array($array[$key])) $array[$key] = [];
    $array = &$array[$key];
  }
  $array[array_shift($keys)] = html_entity_decode($value);
}
function getRutaRecurso(string $id, bool $conZIP = true)
{
  return implode(DIRECTORY_SEPARATOR, str_split(str_pad($id, 9, "0", STR_PAD_LEFT), 3)) . DIRECTORY_SEPARATOR .
    ($conZIP ? NOMBRE_ZIP : "");
}
function debug($texto, $nivel)
{
  $fechaHora = new DateTime();
  $timestampISO = $fechaHora->format(DateTime::ATOM);
  $salida = $timestampISO . (($nivel < 0) ? " " : str_repeat(" ", $nivel) . " └─ ") . $texto . PHP_EOL;
  file_put_contents("debug_csvToJson.txt", $salida, FILE_APPEND);
  if ($nivel < 4) print $salida;
}
