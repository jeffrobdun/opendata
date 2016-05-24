<?php

require('../config/config.php');
include('extensions/simple_html_dom.php');

$db = new mysqli(DB_SERVER, DB_USER, DB_PASS, SJ_DB_NAME);
$url = 'http://www.saintjohn.ca/en/home/cityhall/financeadmin/informationtechnology/opendata_catalogue/default.aspx';

$html = file_get_html($url);
$anchorTags = $html->find('a[tabindex=-1]');
// print 'count($anchorTags): ' . count($anchorTags) . '<br/>';
ini_set('memory_limit', '-1');
for($i=0;$i<count($anchorTags);$i++){
  $readContents = '';
  $downloadPage = '';
//   print 'i: ' . $i . '<br/>';
  if(strpos($anchorTags[$i]->href, "www.saintjohn.ca/") > -1){
    print $anchorTags[$i]->href . '<br/>';
    $mainDownloadPage = file_get_html($anchorTags[$i]->href);
    $iframes = $mainDownloadPage->find('iframe');
    $downloadPage = file_get_html('http://www.saintjohn.ca/' . $iframes[0]->src);
    $downloadForm = $downloadPage->find('form');
    $downloadUrl = $downloadForm[0]->action;
    $filename = explode('/', $downloadUrl);
    $filename = $filename[count($filename)-1];
    print 'downloadUrl: ' . $downloadUrl;
//     $readContents = file_get_contents($downloadUrl);
//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $downloadUrl);
// 		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 		curl_setopt($ch, CURLOPT_TIMEOUT, 10000);
//     curl_setopt($ch, CURLOPT_SSLVERSION,3);
		
// 		$completeResults = array();
// 		$result = curl_exec($ch);
//     curl_close($ch);
//     printf('result:<pre>%s</pre>',print_r($result, true));
//     file_put_contents($filename, $result); 
		
   	file_put_contents($filename, file_get_contents($downloadUrl));
    chmod($filename, 777);
    $zip = new ZipArchive;
    if ($zip->open($filename) === TRUE) {
      $folderName = str_replace('.zip','',$filename);
      $zip->extractTo('./' . $folderName);
      $zip->close();
    }

    if(file_exists($folderName)){
      unlink($filename);
    }
    if(file_exists($folderName . '/' . $folderName . '.dbf')){
      $handle = fopen($folderName . '/' . $folderName . '.dbf', 'r');
      $readReturn = fread($handle, filesize($folderName . '/' . $folderName . '.dbf'));
      $headers = explode(chr(13), $readReturn);
      $data = $headers[count($headers)-1];
      $headers = $headers[0];
  //     $headerString = '';
  //     for($j=0;$j<count($headers)-2;$j++){
  //       $headerString .= $headers[$j];
  //     }
  //     $headers = $headerString;
//       file_put_contents($folderName . '.txt', $headers);
  //     $headers = str_replace(chr(0), "", $headers);
  //     $headers = explode(html_entity_decode("&mdash;"), $headers);
  //     $headers = $headers[1];

  //     $readReturn = str_replace(" ", "<br/>", $readReturn);
  //     preg_match_all("/^[a-zA-Z]+$/", $headers, $matches);
      $matches = array();
      preg_match_all("/[a-zA-Z0-9\_\&]+/", $headers, $matches);
      $data = preg_replace("/   +/", "+", $data);
      $data = explode('+', $data);

      $matches = $matches[0];
      $headers = array();
      for($j=1;$j<count($matches);$j++){
        if(strlen($matches[$j]) > 1)
          array_push($headers, $matches[$j]);
      }
      $dropTableCommand="DROP TABLE IF EXISTS " . $folderName . ";";
      $createTableCommand = "CREATE TABLE " . $folderName . "(`" . implode("` VARCHAR(150), `", $headers) . "` VARCHAR(150));" ;
      print $createTableCommand;
      if(!$result = $db->query($dropTableCommand)){
        die('There was an error running the query [' . $db->error . ']<br/>');
      }
      if(!$result = $db->query($createTableCommand)){
        die('There was an error running the query [' . $db->error . ']<br/>');
      }

      $validRange = range(1,100);
      for($j=0;$j<count($data);$j+=count($headers)){
        $insertCommand = "INSERT INTO " . $folderName . "(" . implode(", ", $headers) . ") VALUES('";
        $insertValues = array();
        $validInsert = true;
        for($k=0;$k<count($headers);$k++){
          if(in_array(strlen($data[$j+$k]), $validRange)){
            $insertValues[] = $db->real_escape_string($data[$j+$k]);          
          }else{
            $badInsertCommand = "Bad Insert For Value " . $data[$j+$k] . ": INSERT INTO " . $folderName . "(" . implode(", ", $headers) . ") VALUES('" . $db->real_escape_string($data[$j+$k]) . "');";
            // Log the error
            print $badInsertCommand .'<br/>';
            // Adjust index so that it's right again, hopefully
            $j += 1;
            $validInsert = false;
          }
        }
        if($validInsert){
          $insertCommand .= implode("', '", $insertValues);
          $insertCommand .= "');";
          print $insertCommand . '<br/>';
          if(!$result = $db->query($insertCommand)){
            die('There was an error running the query [' . $db->error . ']<br/>');
          }
        }
      }

//     $resultsArray = array();

//     while($databaseResult = $result->fetch_assoc())
//       $resultsArray[] = $databaseResult;

//     $headers = implode('+', $headers);

//     printf('<pre>%s</pre>',print_r($headers, true));
//     $readReturn = str_replace("\t", "<br/>", $readReturn);
//     print $readReturn;
//     dbase_open($folderName . '/' . $folderName);
//     chmod($folderName, 777);
//     chmod($folderName . '/' . $folderName . '.dbf', 777);
//     rename($folderName, 'savedFiles/' . $folderName);
//     if(file_exists('savedFiles/' . $folderName)){
//       unlink($folderName);
//       unlink($filename);
//     }
//      rename($folderName . '/' . $folderName . '.dbf', $folderName . '.dbf');
//      $dbase = dbase_open($folderName . '/' . $folderName . '.dbf', 0);
//       printf('<pre>%s</pre>', print_r($dbase, true));
// //       or die("Error! Could not open dbase database file.");
//       $column_info = dbase_get_header_info($dbase);
//       printf('<pre>%s</pre>', print_r($column_info, true));
//     if(file_exists($folderName . '/' . $folderName . '.dbf')){

// // //     foreach($column_info as $column){
// // //       printf('<pre>%s</pre>', print_r($column, true));
// // //     }
//     }else{
//       print $folderName . '.dbf doesnt exist';
//     }
    }
  }
}

  
