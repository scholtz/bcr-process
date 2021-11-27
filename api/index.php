<?php
$dir = __DIR__ . '/../processes/';

if(isset($_SERVER["REQUEST_URI"]) && substr($_SERVER["REQUEST_URI"],-4)==".svg"){
 $file = $dir.substr($_SERVER["REQUEST_URI"],1,-4).".txt";
 if(file_exists($file)){

  $content = encodep(file_get_contents($file));
  $cachefile = "/tmp/cache-".md5($content).".svg";
  if(file_exists($cachefile)){
   $contentsvg = file_get_contents($cachefile);
   header("Content-Type: image/svg+xml");
   header("X-cache: $cachefile");
   echo $contentsvg;
   exit;
  }
  $contentsvg = file_get_contents('https://www.plantuml.com/plantuml/svg/'.$content);
  header("Content-Type: image/svg+xml");
  echo $contentsvg;
  @file_put_contents($cachefile, $contentsvg);
  exit;
 }
}

$files = scandir($dir);
foreach($files as $file){
  if(substr($file,-4) != ".txt") continue;
  echo '<h2><a href="https://github.com/scholtz/bcr-process/edit/main/processes/'.$file.'">'.$file.'</a></h2>';
  $svgfilename = substr($file,0,-4).".svg";
  $link = 'https://process.blockchaincarbonregistry.com/'.$svgfilename;

  echo '<pre>'.$link.'</pre>';
  $content = encodep(file_get_contents($dir.$file));
  echo '<a href="https://www.plantuml.com/plantuml/umla/'.$content.'"><img width="100%" src="'.$link.'"></a>'."\n\n";
}


function base64url_encode( $data ){
  return rtrim( strtr( base64_encode( $data ), '+/', '-_'), '=');
}
function base64url_decode( $data ){
  return base64_decode( strtr( $data, '-_', '+/') . str_repeat('=', 3 - ( 3 + strlen( $data )) % 4 ));
}





/**
 * Encodes a UML text description
 *
 * @param string $pumlCode PlantUml diagram, encoded in UTF8
 *
 * @return string Encoded string
 * @throws \Exception
 */
function encodep($pumlCode)
{
    $compressed = gzdeflate($pumlCode, 9);

    if (false === $compressed) {
        throw new \Exception('Error while compressing PlantUml diagram');
    }

    return encode64($compressed);
}

/**
 * @param int $b
 *
 * @return string
 */
function encode6bit($b)
{
    if ($b < 10) {
        return chr(48 + $b);
    }
    $b -= 10;
    if ($b < 26) {
        return chr(65 + $b);
    }
    $b -= 26;
    if ($b < 26) {
        return chr(97 + $b);
    }
    $b -= 26;
    if ($b == 0) {
        return '-';
    }
    if ($b == 1) {
        return '_';
    }

    return '?';
}

/**
 * @param int $b1
 * @param int $b2
 * @param int $b3
 *
 * @return string
 */
function append3bytes($b1, $b2, $b3)
{
    $c1 = $b1 >> 2;
    $c2 = (($b1 & 0x3) << 4) | ($b2 >> 4);
    $c3 = (($b2 & 0xF) << 2) | ($b3 >> 6);
    $c4 = $b3 & 0x3F;
    $r  = '';
    $r  .= encode6bit($c1 & 0x3F);
    $r  .= encode6bit($c2 & 0x3F);
    $r  .= encode6bit($c3 & 0x3F);
    $r  .= encode6bit($c4 & 0x3F);

    return $r;
}

/**
 * @param string $c Compressed string
 *
 * @return string
 */
function encode64($c)
{
    $str = '';
    $len = strlen($c);
    for ($i = 0; $i < $len; $i += 3) {
        if ($i + 2 === $len) {
            $str .= append3bytes(ord(substr($c, $i, 1)), ord(substr($c, $i + 1, 1)), 0);
        } elseif ($i + 1 === $len) {
            $str .= append3bytes(ord(substr($c, $i, 1)), 0, 0);
        } else {
            $str .= append3bytes(ord(substr($c, $i, 1)),
                                 ord(substr($c, $i + 1, 1)),
                                 ord(substr($c, $i + 2, 1)));
        }
    }

    return $str;
}