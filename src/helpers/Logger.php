<?php
  
  namespace PF\helpers;
  
  use Tracy\Debugger;
  use ZipArchive;
  
  /**
   * Třída Logger.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   08.02.2019
   */
  class Logger
  {
    
    const LOG_TYPE_JSON = 'json';
    const LOG_TYPE_TXT  = 'txt';
    const LOG_TYPE_LOG  = 'log';
    
    private $allowedTypes = [
      self::LOG_TYPE_JSON,
      self::LOG_TYPE_TXT,
      self::LOG_TYPE_LOG,
    ];
    
    private $fileContent = NULL;
    private $fileContentArray = [];
    private $dir = NULL;
    private $fileName = NULL;
    private $prefix = NULL;
    private $type = self::LOG_TYPE_JSON;
    
    private $timeOld           = '7 days';
    private $archiveNoDelete   = FALSE;
    private $archiveFolderName = 'archive';
    
    private $changed = FALSE;
    private $new = TRUE;
    
    public function __construct($prefix, $fileName, $dir = NULL, $type = self::LOG_TYPE_JSON) {
      $this->prefix = $prefix;
      $this->fileName = $fileName;
      $this->dir = rtrim(((is_null($dir)) ? __DIR__ . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR : $dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
      $this->type = $type;
      
      $this->checkAllowedType();
      $this->load();
    }
    
    /**
     * Vrátí typ souboru.
     * @return mixed|string
     */
    private function getType() {
      return $this->type;
    }
    
    /**
     * Zkontroluje zda je soubor povoleného typu.
     * @return bool
     */
    private function checkAllowedType() {
      return in_array($this->getType(), $this->allowedTypes);
    }
    
    /**
     * Vrátí celou cestu k souboru.
     * @return string
     */
    public function getFilePath() {
      return $this->dir . $this->prefix . $this->fileName . '.' . $this->type;
    }
    
    /**
     * Vrátí zda soubor existuje či neexistuje.
     * @return bool
     */
    private function exists() {
      return file_exists($this->getFilePath());
    }
    
    /**
     * Vytvoří rekurzivně logovací adresář, pokud ještě neexistuje.
     */
    private function createDir() {
      if (!is_dir($this->dir)) {
        File::mkDir($this->dir, TRUE);
      }
    }
    
    /**
     * Upraví řádek do správného tvaru.
     *
     * @param      $content
     * @param bool $addDate
     *
     * @return array
     * @throws \Exception
     */
    private function prepareLine($content, $addDate = TRUE) {
      $data = [];
      
      if (is_array($content)) {
        $data = $content;
      } elseif (is_string($content)) {
        $data = [$content];
      }
      
      if ($addDate) {
        $data['date'] = (new \DateTime('now'))->format('Y-m-d H:i:s.u');
      }
      
      return $data;
    }
    
    /**
     * Přidá záznam na konec souboru.
     *
     * @param string|array $content
     * @param bool         $addDate
     *
     * @return $this
     * @throws \Exception
     */
    public function append($content, $addDate = TRUE) {
      $this->fileContentArray[] = $this->prepareLine($content, $addDate);
      $this->changed = TRUE;
      
      return $this;
    }
    
    /**
     * Přidá záznam na konec souboru a uloží.
     *
     * @param string|array $content
     * @param bool         $addDate
     *
     * @return $this
     * @throws \Exception
     */
    public function appendStore($content, $addDate = TRUE) {
      $this->append($content, $addDate)->store();
      
      return $this;
    }
    
    /**
     * Přidá záznam na začátek souboru.
     *
     * @param string|array $content
     * @param bool         $addDate
     *
     * @return $this
     * @throws \Exception
     */
    public function prepend($content, $addDate = TRUE) {
      $this->fileContentArray = array_merge([$this->prepareLine($content, $addDate)], (array)$this->fileContentArray);
      $this->changed = TRUE;
      
      return $this;
    }
    
    /**
     * Přidá záznam na začátek souboru a uloží.
     *
     * @param string|array $content
     * @param bool         $addDate
     *
     * @return $this
     * @throws \Exception
     */
    public function prependStore($content, $addDate = TRUE) {
      $this->prepend($content, $addDate)->store();
      
      return $this;
    }
    
    /**
     * Nastaví obsah souboru. Lze použít jen pokud se jedná o nový soubor a není typu JSON, jinak použíjte metody
     * {@link \prosys\Logger::append} nebo {@link \prosys\Logger::prepend}.
     *
     * @param $string
     */
    public function setContent($string) {
      if (is_null($this->fileContent) && $this->type != self::LOG_TYPE_JSON) {
        $this->fileContentArray = [$string];
        $this->changed = TRUE;
      }
    }
    
    /**
     * Rozparsuje data a uloží do pole.
     */
    private function parseData() {
      if (!is_null($this->fileContent) && !$this->fileContentArray) {
        switch ($this->getType()) {
          case self::LOG_TYPE_TXT:
          case self::LOG_TYPE_LOG:
            $this->fileContentArray = (array)explode(PHP_EOL, $this->fileContent);
            break;
          
          case self::LOG_TYPE_JSON:
          default:
            $this->fileContentArray = json_decode($this->fileContent, TRUE);
            break;
        }
      }
    }
    
    /**
     * Načte obsah souboru do fileContent.
     * @return $this
     */
    private function load() {
      if ($this->exists()) {
        $this->fileContent = file_get_contents($this->getFilePath());
        $this->new = FALSE;
        
        $this->parseData();
      }
      
      return $this;
    }
    
    /**
     * Uloží log do souboru.
     *
     * @return bool|null
     *  NULL = nebyl změně (nebylo třeba ukládat),<br/>
     *  TRUE = uloženo v pořádku,<br/>
     *  FALSE = chyba při ukládání.
     */
    public function store() {
      $storeRes = NULL;
      
      if ($this->changed) {
        $this->createDir();
        
        if ($this->exists() && $this->new) {
          $this->fileName = MyString::seoTypeConversion($this->fileName . '_' . date('Ymd_His'));
        }
        
        switch ($this->getType()) {
          case self::LOG_TYPE_TXT:
          case self::LOG_TYPE_LOG:
            $string = implode(PHP_EOL, array_map(function($row) {
              $res = '';
              if (is_string($row)) {
                $res = (string)$row;
              } elseif (is_array($row)) {
                if (array_key_exists('date', $row)) {
                  $res .= "[{$row['date']}]: ";
                  unset($row['date']);
                }
                $res .= var_export($row, TRUE);
              }
              
              return $res;
            }, $this->fileContentArray));
            break;
          
          case self::LOG_TYPE_JSON:
          default:
            $string = json_encode($this->fileContentArray, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
            break;
        }
        
        if (($storeRes = (bool)file_put_contents($this->getFilePath(), $string))) {
          if (file_exists($this->getFilePath())) {
            $oldUmask = umask(0);
            chmod($this->getFilePath(), 0777);
            umask($oldUmask);
          }
          
          $this->fileContent = $string;
          $this->changed = FALSE;
          $this->setNew(FALSE);
        }
      }
      
      $this->deleteOld();
      
      return $storeRes;
    }
    
    /**
     * Smaže staré soubory.
     */
    public function deleteOld() {
      $files = glob($this->dir . $this->prefix . '*.' . $this->type);
      $timeToCompare = strtotime('-' . $this->timeOld);
      
      $processArchive = FALSE;
      foreach ($files as $file) {
        if (is_file($file) && filemtime($file) < $timeToCompare) {
          if ($this->archiveNoDelete) {
            $filename = basename($file);
            $dir = rtrim(dirname($file), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->archiveFolderName . DIRECTORY_SEPARATOR;
            if (!is_dir($dir)) {
              File::mkDir($dir);
            }
            
            $processArchive = rename($file, $dir . $filename) || $processArchive;
          } else {
            unlink($file);
          }
        }
      }
      
      if ($processArchive) {
        $this->processArchiveFolder();
      }
    }
    
    /**
     * Zabali soubory v archivu
     */
    private function processArchiveFolder() {
      $archives = [];
      $folder = $this->dir . $this->archiveFolderName . DIRECTORY_SEPARATOR;
      
      $files = glob($folder . $this->prefix . '*');
      foreach ($files as $file) {
        $file = array_merge([
          'filePath' => $file,
          'modified' => filemtime($file),
        ], pathinfo($file));
        
        $zipName = MyString::seoTypeConversion(
          date('Y.m.d', $file['modified']) . ' ' . $this->prefix,
          '_'
        );
        if (!array_key_exists($zipName, $archives)) {
          $archives[$zipName] = [
            'files' => [],
          ];
        }
        
        $archives[$zipName]['files'][] = $file;
      }
      
      foreach ($archives as $zipFileName => $zipData) {
        $zip = new \ZipArchive();
        $toDeleteFiles = [];
        $zip->open($folder . $zipFileName . '.zip', ZipArchive::CREATE);
        foreach ($zipData['files'] as $archiveFile) {
          if ($zip->addFile($archiveFile['filePath'], str_replace($folder, '', $archiveFile['filePath']))) {
            $toDeleteFiles[] = $archiveFile['filePath'];
          }
        }
        $zip->close();
        
        foreach ($toDeleteFiles as $toDeleteFile) {
          unlink($toDeleteFile);
        }
      }
    }
    
    /**
     * Vrátí zda byl soubor změněn či nikoli.
     *
     * @return bool
     */
    public function wasChanged() {
      return $this->changed;
    }
    
    /**
     * Zjistí zda je soubor nový.
     *
     * @return bool
     */
    public function isNew() {
      return $this->new;
    }
    
    /**
     * Nastaví zda se jedná o nový či starý soubor.
     *
     * @param bool $bool
     */
    public function setNew($bool = TRUE) {
      $this->new = $bool;
    }
    
    /**
     * Vrátí instanci třídy Logger a načte obsah souboru pokud existuje.
     *
     * @param        $prefix
     * @param        $fileName
     * @param null   $dir
     * @param string $type
     *
     * @return Logger
     */
    public static function create($prefix, $fileName, $dir = NULL, $type = self::LOG_TYPE_JSON) {
      $logger = new self($prefix, $fileName, $dir, $type);
      
      return $logger;
    }
    
    /**
     * Nastaví dobu kešování souborů v historii.
     *
     * @param string $stringTime string pro strtotime()
     */
    public function setCacheTime($stringTime = '7 days') {
      $this->timeOld = $stringTime;
    }
    
    public function archiveNoDelete($archiveNoDelete = TRUE) {
      $this->archiveNoDelete = $archiveNoDelete;
      
      return $this;
    }
    
    /**
     * Vrátí obsah souboru.
     *
     * @return string
     */
    public function __toString() {
      return (string)$this->fileContent;
    }
  }