<?php
require __DIR__ . '/vendor/autoload.php';
use PHPHtmlParser\Dom;

$args = [];
for ($i = 0; $i<sizeof($argv); $i++) {
  if (strpos($argv[$i], '-') === 0) {
    $args[$argv[$i]] = $argv[$i + 1];
  }
}
$dir = $args['-dir'];
$board = $args['-b'];

if (!file_exists($dir)) {
  mkdir($dir, 0777, true);
}
$dom = new Dom;
$hrefs = [];
$base_href = 'https://boards.4chan.org/'.$board.'/';
for ($i = 1; $i <=10; $i++) {
    $index = $i == 1 ? '' : $i;
    $dom->loadFromUrl($base_href.$index);
    $anchors = $dom->getElementsbyTag('a');
    foreach ($anchors as $anchor) {
        $class = $anchor->getAttribute('class');
        $href_particles = explode('/', $anchor->getAttribute('href'));
        if (sizeof($href_particles) >= 2) {
            $path = $href_particles[0].'/'.$href_particles[1];
        } else {
            $path = '';
        }
        if ($class == 'replylink'  && !in_array($path, $hrefs)) {
            $hrefs[] = $path;
            $dom->loadFromUrl($base_href.$path);
            $file_texts = $dom->find('.fileText');
            foreach ($file_texts as $file_text) {
                $anchor = $file_text->find('a');
                $img_href = $anchor->getAttribute('href');
                $img_name = explode('/', $img_href)[4];
                $full_url = 'https:'.$img_href;
                if (file_exists($dir.$img_name)) {
                    echo 'Skipping '.$full_url.' because it already exists.'.PHP_EOL;
                } else {
                    echo 'Downloading '.$full_url.'...'.PHP_EOL;
                    download_file($full_url, $dir, $img_name);
                }
            }
        }
    }
}

function download_file($file_url, $save_to, $img_name)
{
    set_time_limit(0);
    $fp = fopen($save_to.'/'.$img_name, 'w+');
    $ch = curl_init(str_replace(" ", "%20", $file_url));
    curl_setopt($ch, CURLOPT_TIMEOUT, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 400);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
}
