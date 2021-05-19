<?php
  
  namespace PF\helpers;
  
  use ZipArchive;
  
  /**
   * Třída Logger.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   08.02.2019
   */
  class BaseLogger
  {
    const LOG_TYPE_LOG  = 'log';
    
    private $allowedTypes = [
      self::LOG_TYPE_LOG,
    ];
    
    private $fileContentArray = [];
    private $dir = NULL;
    private $fileName = NULL;
    private $prefix = NULL;
    private $type = self::LOG_TYPE_LOG;
    
    private $timeOld           = '7 days';
    private $archiveNoDelete   = FALSE;
    private $archiveFolderName = 'archive';
    
    private $changed = FALSE;
    private $new = TRUE;
    
    public function __construct($prefix, $fileName, $dir = NULL, $type = self::LOG_TYPE_LOG) {
      $this->prefix = $prefix;
      $this->fileName = $fileName;
      $this->dir = rtrim(((is_null($dir)) ? __DIR__ . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR : $dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
      $this->type = $type;
      
      $this->checkAllowedType();
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
        File::mkDir($this->dir);
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
          $this->fileName = MyString::seoTypeConversion($this->fileName . '_' . date('Ymd_His_u'));
        }
  
        $storeRes = FALSE;
        switch ($this->getType()) {
          default:
          case self::LOG_TYPE_LOG:
            $storeRes = (bool)file_put_contents($this->getFilePath(), implode(PHP_EOL, array_map(function($row) {
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
            }, $this->fileContentArray)));
            break;
        }
        
        if ($storeRes) {
          $this->fileContentArray = [];
          
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
              if (!File::mkDir($dir)) {
                throw new \Exception(__CLASS__ . ' not create dir `' . $dir . '`.');
              }
            }
            
            $processArchive = rename($file, $dir . $filename) || $processArchive;
          } else {
            @unlink($file);
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
        $zip = new ZipArchive();
        $toDeleteFiles = [];
        $zipName = $folder . $zipFileName . '.zip';
        if (TRUE === ($zip->open($zipName, ((is_file($zipName)) ? NULL : ZipArchive::CREATE)))) {
          foreach ($zipData['files'] as $archiveFile) {
            if (is_file($archiveFile['filePath']) && $zip->addFile($archiveFile['filePath'], str_replace($folder, '', $archiveFile['filePath']))) {
              $toDeleteFiles[] = $archiveFile['filePath'];
            }
          }
          
          $zip->close();
        }
        
        foreach ($toDeleteFiles as $toDeleteFile) {
          if (is_file($toDeleteFile)) {
            @unlink($toDeleteFile);
          }
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
     * @return BaseLogger
     */
    public static function create($prefix, $fileName, $dir = NULL, $type = self::LOG_TYPE_LOG) {
      return new self($prefix, $fileName, $dir, $type);
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
      return (string)file_get_contents($this->getFilePath());
    }
  }