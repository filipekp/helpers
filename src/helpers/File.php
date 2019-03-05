<?php
  
  namespace PF\helpers;
  
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
      return trim($mimeType);
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
     * @param $dirPath
     */
    public static function deleteDir($dirPath) {
      if (!is_dir($dirPath)) { throw new \InvalidArgumentException("{$dirPath} must be a directory"); }
      if (substr($dirPath, strlen($dirPath) -1, 1) != '/') { $dirPath .= '/'; }
    
      $files = glob($dirPath . '*', GLOB_MARK);
      foreach ($files as $file) {
        if (is_dir($file)) {
          self::deleteDir($file);
        } else {
          unlink($file);
        }
      }
    
      rmdir($dirPath);
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
  }