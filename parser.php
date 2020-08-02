<?php

function dd($var)
{
  echo '<pre>';
  var_dump($var);
  echo '</pre>';
  exit();
}

function formatDate($date, $format = 'm/d/y')
{
  return date($format, (int) $date);
}

class WSJ
{
  protected $baseUri; // https://www.wsj.com/market-data/quotes
  public $url; // https://www.wsj.com/market-data/quotes/$name/historical-prices/download?MOD_VIEW=page&num_rows=&range_days=&startDate=$startDate&endDate=$endDate;

  public function __construct($name, $startDate, $endDate)
  {
    $this->baseUri = 'https://www.wsj.com/market-data/quotes';
    $days = $numRows = $rangeDays = ($endDate - $startDate) / 60 / 60 / 24;

    $args = array(
      'MOD_VIEW' => 'page',
      'num_rows' => $numRows,
      'range_days' => $rangeDays,
      'startDate' => formatDate($startDate),
      'endDate' => formatDate($endDate),
    );

    $parts = array(
      $this->baseUri,
      implode('/', explode(':', $name)),
      "historical-prices",
      "download?" . implode('&', array_map(function ($v, $k) {
        return $k . '=' . $v;
      }, array_values($args), array_keys($args))),
    );

    $this->url = implode('/', $parts);
  }
}

class Parser
{
  public $data;

  public function __construct($data)
  {
    $this->data = array_slice(explode(PHP_EOL, $data), 1);
  }

  public function parse()
  {
    return array_map(function ($v) {
      $a = explode(',', $v);

      $values = array();
      if (isset($a[0])) {
        array_push($values, date('d.m.Y', strtotime($a[0])));
      }

      if (isset($a[5])) {
        array_push($values, strtr($a[5], '.', ','));
      }

      if (isset($a[4])) {
        array_push($values, strtr($a[4], '.', ','));
      }

      return implode(';', $values) . PHP_EOL;
    }, $this->data);
  }
}

class Request
{
  protected $url;

  public function __construct($url)
  {
    $this->url = $url;
  }

  public function make()
  {
    $agent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.89 Safari/537.36';
    $ch = curl_init();

    curl_setopt_array($ch, array(
      CURLOPT_REFERER => "https://www.wsj.com/market-data/quotes/",
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_URL => $this->url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_VERBOSE => true,
      CURLOPT_USERAGENT => $agent,
      CURLOPT_ENCODING => 'gzip, deflate',
      CURLOPT_HTTPHEADER => array(
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language: en-US,en;q=0.5',
        'Connection: keep-alive',
        'Upgrade-Insecure-Requests: 1',
        'Content-Description: File Transfer',
        'Content-Transfer-Encoding: binary',
        'Connection: Keep-Alive',
        'Content-Type: text/csv',
        'Expires: 0',
        'Cache-Control: must-revalidate, post-check=0, pre-check=0',
        'Pragma: public',
      ),
    ));

    $response = curl_exec($ch);

    $info = curl_getinfo($ch);

    curl_close($ch);

    // dd($info);

    return $response;
  }
}

class File
{
  protected $folder;
  protected $data;
  protected $name;

  public function __construct($name)
  {
    $this->folder = realpath('');
    $this->name = strtr($name, ':', '.') . '.csv';
  }

  public function setData($data)
  {
    $this->data = $data;
    return $this;
  }

  public function save()
  {
    if (!is_dir($this->folder . '/tickets/')) {
      mkdir($this->folder . '/tickets/');
    }

    return file_put_contents($this->folder . '/tickets/' . $this->name, $this->data);
  }
}

try {

  try {
    $names = explode(PHP_EOL, file_get_contents($_FILES['names']['tmp_name']));
    $startDate = strtotime($_POST['startDate']) ?? 1537574400;
    $endDate = strtotime($_POST['endDate']) ?? 1545436800;
  } catch (Exception $e) {
    var_dump($e);
    exit();
  }

  foreach ($names as $key => $value) {

    $name = trim($value);

    if (empty($name)) {
      continue;
    }

    try {
      $wsj = new WSJ($name, $startDate, $endDate);

      $request = new Request($wsj->url);
      $response = $request->make();

      $parser = new Parser($response);
      $parsed = $parser->parse();

      $file = new File($key . '.' . $name);
      $saved = $file->setData($parsed)->save();
    } catch (Exception $e) {
      var_dump($e);
    }

    if ($key > 2) {
      //exit();
    }
  }

  var_dump("Done");
} catch (Exception $e) {
  var_dump($e);
}
