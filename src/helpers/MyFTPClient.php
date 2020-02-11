<?php
  
  namespace PF\helpers;
  
  use DateTime;
  use DateTimeZone;
  use FtpClient\FtpClient;

  /**
   * Třída MyFTPClient reprezentující klienta pro připojení k FTP. Implementace vlastních funkcí.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2020, Proclient s.r.o.
   * @created   10.02.2020
   */
  class MyFTPClient extends FtpClient
  {
    /** @var Logger */
    protected $logger = NULL;
  
    public function __construct(resource $connection = NULL, &$logger = NULL) {
      parent::__construct($connection);
    
      $this->logger = $logger;
    }
  
    /**
     * @param      $content
     * @param bool $addDate
     *
     * @return $this
     * @throws \Exception
     */
    private function appendStore($content, $addDate = TRUE) {
      if (!is_null($this->logger)) {
        $this->logger->appendStore($content, $addDate);
      }
      
      return $this;
    }
  
    /**
     * Smaže soubory v předaném adresáři starší jak zvolené datum.
     *
     * @param        $ftpDir
     * @param string $time
     *
     * @throws \Exception
     */
    public function removeByTime($ftpDir, $time = NULL) {
      $time = (is_null($time) ? strtotime('-7 days') : $time);
      $dateToCompare = DateTime::createFromFormat('YmdHis', date('YmdHis', $time));
      $filesToSkip = ['.ftpquota'];
    
      $this->appendStore([
        'Start delete old files.',
        'ftpDir' => $ftpDir,
        '',
        'Files older than: ' . $dateToCompare->format('d.m.Y H:i:s'),
        'Skipped files: `' . implode('`, `', $filesToSkip) . '``',
      ]);
    
      $filesToDelete = array_filter($this->mlsd($ftpDir), function($fileInfo) use ($filesToSkip, $dateToCompare) {
        $toDelete = $fileInfo['type'] == 'file';
        $toDelete = $toDelete && !in_array($fileInfo['name'], $filesToSkip);
        $toDelete = $toDelete && preg_match('@^.*\.sql\.gz$@', $fileInfo['name']) != FALSE;
        $fileTime = DateTime::createFromFormat('YmdHis', $fileInfo['modify'])->setTimezone((new DateTimeZone('Europe/Prague')));
        $toDelete = $toDelete && ($fileTime < $dateToCompare);
      
        return $toDelete;
      });
    
      foreach ($filesToDelete as $fileToDelete) {
        $deletedRes = $this->delete($ftpDir . $fileToDelete['name']);
        $this->appendStore([
          'File `' . $fileToDelete['name'] . '` is' . (($deletedRes) ? '' : ' NOT') . ' deleted.'
        ]);
      }
    
      $this->appendStore([
        'End delete old files.'
      ]);
    }
  }