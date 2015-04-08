<?php
define('BASEPATH',__DIR__);

require_once('./config.php');
require_once('./lib/upyun.class.php');

if (empty($_FILES)){
  echo 'empty file.';
  exit(0);
}

$file = $_FILES['file'];
$file_type = $file['type'];
$file_size = $file['size'];
$temp_file = $file['tmp_name'];

$uppath = BASEPATH.'/uploads/'.date("Ymd").'/';
$file_name = basename($file['name']);
$real_name = time().'_'.$file_name;
$target_file = str_replace('//','/',$uppath).$real_name;
$upyun_file = '/'.date("Ymd").'/'.$real_name ;

// when use IE, type is :  application/octet-streamimg
$mime = mime_content_type ($temp_file);
if((strpos($file_type, 'image/')!==0)  &&  (strpos($mime, 'image/')!==0 ) ){
  echo ('img allowed only,FILE mime:'.$file_type.',mime_content_type:'.$mime);
  exit(-1);
}

if(intval($file_size) > IMAGE_MAX_SIZE ){
  echo 'img is larger than 8MB.';
  exit(-2);
}

if (!is_dir($uppath)) {
    @umask(0);
    $ret = @mkdir($uppath, 0777);
    if ($ret === false) {
        echo "dir access deny.";
        exit(-3);
    }
}

if(is_uploaded_file($temp_file)===TRUE  &&  move_uploaded_file($temp_file, $target_file) ===TRUE ){
  $domain = BUCKET_DOMAIN;
  $upyun = new UpYun(BUCKET_NAME, BUCKET_USER, BUCKET_PASSWORD);
  $img_uri = "http://${domain}${upyun_file}!m";

  try {
      // echo "=========upload\r\n";
      $fh = fopen($target_file, 'rb');
      $rsp = $upyun->writeFile($upyun_file, $fh, True);   // 上传图片，自动创建目录
      fclose($fh);
      // var_dump($rsp);
      if(is_array($rsp) && !empty($rsp)){
          $width_height =  $rsp['x-upyun-width']."x".$rsp['x-upyun-height'];
          $file_type =  $rsp['x-upyun-file-type'];
          echo "<img src=\"${img_uri}\">\r\n<br>";
          echo "Image URL: $img_uri \r\n<br>";
          echo "MarkdownIMG: ![${file_name}](${img_uri} \"opt:${file_name}\") \r\n<br>" ;
      }
  }
  catch(Exception $e) {
      echo $e->getCode();
      echo $e->getMessage();
  }

}else{
  echo 'is_uploaded_file and move_uploaded_file failed.';
  exit(-4);
}
?>
