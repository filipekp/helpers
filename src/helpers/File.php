<?php
  
  namespace PF\helpers;
  
  use finfo;

  /**
   * Třída File reprezentující ...
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   07.02.2019
   */
  class File
  {
    /**
     * Vrati prava lokalniho souboru.
     *
     * @param string $filepath
     *
     * @return integer return octal type number
     */
    public static function localPermissions($filepath) {
      return octdec(substr(sprintf('%o', fileperms($filepath)), -4));
    }
  
    /**
     * Rozparsuje velikost s pripadnou jednotkou a vrati hodnotu v bajtech.
     *
     * @param float $size
     *
     * @return float
     */
    public static function parseIniSize($size) {
      $units = 'bkmgtpezy';   // vsechny mozne jednotky serazene od nejmensi (bajt) k nejvetsimu (yottabajt)
    
      $unit = preg_replace("/[^{$units}]/i", '', $size);
      $number = preg_replace('/[^0-9\.]/', '', $size);
    
      // v pripade, ze je velikost dana s jednotkou, bude nasobit 1024^n, kde n je pozice jednotky v serazenem retezci jednotek
      return $number * (($unit) ? pow(1024, stripos($units, $unit[0])) : 1);
    }
  
    /**
     * Vrátí naformátované číslo velikosti souboru.
     * @param $size
     *
     * @return string
     */
    public static function getFormatedFilesize($size) {
      $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
      $power = $size > 0 ? floor(log($size, 1024)) : 0;
    
      return number_format($size / pow(1024, $power), 2, ',', ' ') . '' . $units[$power];
    }
  
    /**
     * Vrati maximalni moznou velikost povolenou serverem pro upload souboru.
     *
     * @return int
     */
    public static function getMaxUploadFileSize() {
      $maxSize = self::parseIniSize(ini_get('post_max_size'));
      $uploadMax = self::parseIniSize(ini_get('upload_max_filesize'));
    
      if ($uploadMax && $uploadMax < $maxSize) {
        $maxSize = $uploadMax;
      }
    
      return (($maxSize) ? $maxSize : 999999999);
    }
  
    /**
     * Vrati unikatni nazev souboru na dane ceste.<br />
     * Tzn. neexistuje-li soubor, vrati primo $filepath, existuje-li, vrati soubor s podtrzitkem a indexem (dle poctu duplicitnich souboru).
     *
     * @param string $filepath
     * @param string $extension parametr je jen pro "efektivitu", metoda nemusi zjistovat priponu souboru
     *
     * @return string
     */
    public static function getUniqueFilename($filepath, $extension = '') {
      $info = (($extension) ? ['extension' => $extension] : pathinfo($filepath));
    
      $extension = '.' . $info['extension'];
      $base = substr($filepath, 0, -strlen($extension));
    
      $index = -1;
      do {
        $index++;
      
        $filepath = $base . (($index) ? '_' . $index : '') . $extension;
        $exists = file_exists($filepath);
      } while ($exists);
    
      return $filepath;
    }
  
    /**
     * Provede bezpecne vlozeni souboru pres require|require_once.
     *
     * @param string $file
     * @param bool   $once
     */
    public static function requireFile($file, $once = FALSE) {
      $suffix = (($once) ? '_once' : '');
      if (file_exists($file)) {
        call_user_func('require' . $suffix, $file);
      }
    }
  
    /**
     * Vrati informaci o vzdalenem souboru pomoci cURL.
     *
     * @param string $url
     * @param int    $info konstanta CURLINFO_{$KONSTANTA}
     *
     * @return string
     */
    public static function getCurlInfo($url, $info) {
      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_NOBODY, TRUE);
      curl_exec($curl);
    
      $response = curl_getinfo($curl, $info);
      curl_close($curl);
    
      return $response;
    }
  
    /**
     * Overi existenci vzdaleneho souboru.
     *
     * @param string $url zacina retezcem http(s)://
     *
     * @return bool
     */
    public static function remoteFileExists($url) {
      return (int)self::getCurlInfo($url, CURLINFO_HTTP_CODE) === 200;
    }
  
    /**
     * Zjisti MIME typ souboru.
     *
     * @param string $url zacina retezcem http(s)://
     *
     * @return string
     */
    public static function remoteMimeType($url) {
      list($mimeType) = explode(';', self::getCurlInfo($url, CURLINFO_CONTENT_TYPE));
      $mime = trim($mimeType);
      
      if (!$mime) {
        $file_info = new finfo(FILEINFO_MIME_TYPE);
        $mime = $file_info->buffer(file_get_contents($url));
      }
      
      return $mime;
    }
  
    /**
     * Zjisti velikost vzdaleneho souboru.
     *
     * @param string $url
     *
     * @return int
     */
    public static function remoteFilesize($url) {
      return (int)self::getCurlInfo($url, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
    }
  
    /**
     * Vytvoří rekurzivně adresářovou strukturu.
     *
     * @param string $dir
     * @param bool   $recursive
     *
     * @return bool
     */
    public static function mkDir($dir, $recursive = TRUE) {
      $r = TRUE;
      $oldUmask = umask(0);
      $r = $r && mkdir($dir, 0777, $recursive);
      umask($oldUmask);
    
      return $r;
    }
  
    /**
     * Smaže složku včetně podložek a souborů.
     *
     * @param      $dirPath
     * @param bool $onlyEmpty
     *
     * @return bool
     */
    public static function deleteDir($dirPath, $onlyEmpty = FALSE) {
      if (!is_dir($dirPath)) { throw new \InvalidArgumentException("`{$dirPath}` must be a directory"); }
      if (substr($dirPath, strlen($dirPath) -1, 1) != DIRECTORY_SEPARATOR) { $dirPath .= DIRECTORY_SEPARATOR; }
    
      $empty = TRUE;
      $files = glob($dirPath . '*', GLOB_MARK);
      foreach ($files as $file) {
        if (is_dir($file)) {
          $empty &= self::deleteDir($file, $onlyEmpty);
        } elseif (!$onlyEmpty) {
          unlink($file);
        }
      }
    
      return $empty && rmdir($dirPath);
    }
  
    /**
     * Vrátí poslední modifikovaný soubor.
     *
     * @param $directoryPath
     *
     * @return array ['path' => null, 'timestamp' => 0]
     */
    public static function getLatestFile($directoryPath) {
      $directoryPath = rtrim($directoryPath, '/');
    
      $max = ['path' => null, 'timestamp' => 0];
      foreach (glob($directoryPath . '/*') as $path) {
        if (!is_file($path)) {
          continue;
        }
      
        $timestamp = filemtime($path);
        if ($timestamp > $max['timestamp']) {
          $max['path'] = $path;
          $max['timestamp'] = $timestamp;
        }
      }
    
      return $max;
    }
  
    /**
     * Vrati informaci o vzdalenem souboru pomoci cURL.
     *
     * @param string $url
     * @param int    $info konstanta CURLINFO_{$KONSTANTA}
     *
     * @return string
     */
    public static function getRemoteInfo($url, $info) {
      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_NOBODY, TRUE);
      curl_exec($curl);
    
      $response = curl_getinfo($curl, $info);
      curl_close($curl);
    
      return $response;
    }
  
    /**
     * Vrátí příponu souboru dle MIME_TYPE.
     *
     * @param $mime
     *
     * @return bool|string
     */
    public static function getExtensionByMime($mime) {
      $mime_map = [
        'video/3gpp2'                                                               => '3g2',
        'video/3gp'                                                                 => '3gp',
        'video/3gpp'                                                                => '3gp',
        'application/x-compressed'                                                  => '7zip',
        'audio/x-acc'                                                               => 'aac',
        'audio/ac3'                                                                 => 'ac3',
        'application/postscript'                                                    => 'ai',
        'audio/x-aiff'                                                              => 'aif',
        'audio/aiff'                                                                => 'aif',
        'audio/x-au'                                                                => 'au',
        'video/x-msvideo'                                                           => 'avi',
        'video/msvideo'                                                             => 'avi',
        'video/avi'                                                                 => 'avi',
        'application/x-troff-msvideo'                                               => 'avi',
        'application/macbinary'                                                     => 'bin',
        'application/mac-binary'                                                    => 'bin',
        'application/x-binary'                                                      => 'bin',
        'application/x-macbinary'                                                   => 'bin',
        'image/bmp'                                                                 => 'bmp',
        'image/x-bmp'                                                               => 'bmp',
        'image/x-bitmap'                                                            => 'bmp',
        'image/x-xbitmap'                                                           => 'bmp',
        'image/x-win-bitmap'                                                        => 'bmp',
        'image/x-windows-bmp'                                                       => 'bmp',
        'image/ms-bmp'                                                              => 'bmp',
        'image/x-ms-bmp'                                                            => 'bmp',
        'application/bmp'                                                           => 'bmp',
        'application/x-bmp'                                                         => 'bmp',
        'application/x-win-bitmap'                                                  => 'bmp',
        'application/cdr'                                                           => 'cdr',
        'application/coreldraw'                                                     => 'cdr',
        'application/x-cdr'                                                         => 'cdr',
        'application/x-coreldraw'                                                   => 'cdr',
        'image/cdr'                                                                 => 'cdr',
        'image/x-cdr'                                                               => 'cdr',
        'zz-application/zz-winassoc-cdr'                                            => 'cdr',
        'application/mac-compactpro'                                                => 'cpt',
        'application/pkix-crl'                                                      => 'crl',
        'application/pkcs-crl'                                                      => 'crl',
        'application/x-x509-ca-cert'                                                => 'crt',
        'application/pkix-cert'                                                     => 'crt',
        'text/css'                                                                  => 'css',
        'text/x-comma-separated-values'                                             => 'csv',
        'text/comma-separated-values'                                               => 'csv',
        'application/vnd.msexcel'                                                   => 'csv',
        'application/x-director'                                                    => 'dcr',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
        'application/x-dvi'                                                         => 'dvi',
        'message/rfc822'                                                            => 'eml',
        'application/x-msdownload'                                                  => 'exe',
        'video/x-f4v'                                                               => 'f4v',
        'audio/x-flac'                                                              => 'flac',
        'video/x-flv'                                                               => 'flv',
        'image/gif'                                                                 => 'gif',
        'application/gpg-keys'                                                      => 'gpg',
        'application/x-gtar'                                                        => 'gtar',
        'application/x-gzip'                                                        => 'gzip',
        'application/mac-binhex40'                                                  => 'hqx',
        'application/mac-binhex'                                                    => 'hqx',
        'application/x-binhex40'                                                    => 'hqx',
        'application/x-mac-binhex40'                                                => 'hqx',
        'text/html'                                                                 => 'html',
        'image/x-icon'                                                              => 'ico',
        'image/x-ico'                                                               => 'ico',
        'image/vnd.microsoft.icon'                                                  => 'ico',
        'text/calendar'                                                             => 'ics',
        'application/java-archive'                                                  => 'jar',
        'application/x-java-application'                                            => 'jar',
        'application/x-jar'                                                         => 'jar',
        'image/jp2'                                                                 => 'jp2',
        'video/mj2'                                                                 => 'jp2',
        'image/jpx'                                                                 => 'jp2',
        'image/jpm'                                                                 => 'jp2',
        'image/jpeg'                                                                => 'jpeg',
        'image/pjpeg'                                                               => 'jpeg',
        'application/x-javascript'                                                  => 'js',
        'application/json'                                                          => 'json',
        'text/json'                                                                 => 'json',
        'application/vnd.google-earth.kml+xml'                                      => 'kml',
        'application/vnd.google-earth.kmz'                                          => 'kmz',
        'text/x-log'                                                                => 'log',
        'audio/x-m4a'                                                               => 'm4a',
        'application/vnd.mpegurl'                                                   => 'm4u',
        'audio/midi'                                                                => 'mid',
        'application/vnd.mif'                                                       => 'mif',
        'video/quicktime'                                                           => 'mov',
        'video/x-sgi-movie'                                                         => 'movie',
        'audio/mpeg'                                                                => 'mp3',
        'audio/mpg'                                                                 => 'mp3',
        'audio/mpeg3'                                                               => 'mp3',
        'audio/mp3'                                                                 => 'mp3',
        'video/mp4'                                                                 => 'mp4',
        'video/mpeg'                                                                => 'mpeg',
        'application/oda'                                                           => 'oda',
        'audio/ogg'                                                                 => 'ogg',
        'video/ogg'                                                                 => 'ogg',
        'application/ogg'                                                           => 'ogg',
        'application/x-pkcs10'                                                      => 'p10',
        'application/pkcs10'                                                        => 'p10',
        'application/x-pkcs12'                                                      => 'p12',
        'application/x-pkcs7-signature'                                             => 'p7a',
        'application/pkcs7-mime'                                                    => 'p7c',
        'application/x-pkcs7-mime'                                                  => 'p7c',
        'application/x-pkcs7-certreqresp'                                           => 'p7r',
        'application/pkcs7-signature'                                               => 'p7s',
        'application/pdf'                                                           => 'pdf',
        'application/octet-stream'                                                  => 'pdf',
        'application/x-x509-user-cert'                                              => 'pem',
        'application/x-pem-file'                                                    => 'pem',
        'application/pgp'                                                           => 'pgp',
        'application/x-httpd-php'                                                   => 'php',
        'application/php'                                                           => 'php',
        'application/x-php'                                                         => 'php',
        'text/php'                                                                  => 'php',
        'text/x-php'                                                                => 'php',
        'application/x-httpd-php-source'                                            => 'php',
        'image/png'                                                                 => 'png',
        'image/x-png'                                                               => 'png',
        'application/powerpoint'                                                    => 'ppt',
        'application/vnd.ms-powerpoint'                                             => 'ppt',
        'application/vnd.ms-office'                                                 => 'ppt',
        'application/msword'                                                        => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/x-photoshop'                                                   => 'psd',
        'image/vnd.adobe.photoshop'                                                 => 'psd',
        'audio/x-realaudio'                                                         => 'ra',
        'audio/x-pn-realaudio'                                                      => 'ram',
        'application/x-rar'                                                         => 'rar',
        'application/rar'                                                           => 'rar',
        'application/x-rar-compressed'                                              => 'rar',
        'audio/x-pn-realaudio-plugin'                                               => 'rpm',
        'application/x-pkcs7'                                                       => 'rsa',
        'text/rtf'                                                                  => 'rtf',
        'text/richtext'                                                             => 'rtx',
        'video/vnd.rn-realvideo'                                                    => 'rv',
        'application/x-stuffit'                                                     => 'sit',
        'application/smil'                                                          => 'smil',
        'text/srt'                                                                  => 'srt',
        'image/svg+xml'                                                             => 'svg',
        'application/x-shockwave-flash'                                             => 'swf',
        'application/x-tar'                                                         => 'tar',
        'application/x-gzip-compressed'                                             => 'tgz',
        'image/tiff'                                                                => 'tiff',
        'text/plain'                                                                => 'txt',
        'text/x-vcard'                                                              => 'vcf',
        'application/videolan'                                                      => 'vlc',
        'text/vtt'                                                                  => 'vtt',
        'audio/x-wav'                                                               => 'wav',
        'audio/wave'                                                                => 'wav',
        'audio/wav'                                                                 => 'wav',
        'application/wbxml'                                                         => 'wbxml',
        'video/webm'                                                                => 'webm',
        'audio/x-ms-wma'                                                            => 'wma',
        'application/wmlc'                                                          => 'wmlc',
        'video/x-ms-wmv'                                                            => 'wmv',
        'video/x-ms-asf'                                                            => 'wmv',
        'application/xhtml+xml'                                                     => 'xhtml',
        'application/excel'                                                         => 'xl',
        'application/msexcel'                                                       => 'xls',
        'application/x-msexcel'                                                     => 'xls',
        'application/x-ms-excel'                                                    => 'xls',
        'application/x-excel'                                                       => 'xls',
        'application/x-dos_ms_excel'                                                => 'xls',
        'application/xls'                                                           => 'xls',
        'application/x-xls'                                                         => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
        'application/vnd.ms-excel'                                                  => 'xlsx',
        'application/xml'                                                           => 'xml',
        'text/xml'                                                                  => 'xml',
        'text/xsl'                                                                  => 'xsl',
        'application/xspf+xml'                                                      => 'xspf',
        'application/x-compress'                                                    => 'z',
        'application/x-zip'                                                         => 'zip',
        'application/zip'                                                           => 'zip',
        'application/x-zip-compressed'                                              => 'zip',
        'application/s-compressed'                                                  => 'zip',
        'multipart/x-zip'                                                           => 'zip',
        'text/x-scriptzsh'                                                          => 'zsh',
      ];
    
      return ((isset($mime_map[$mime]) === TRUE) ? $mime_map[$mime] : FALSE);
    }
  }