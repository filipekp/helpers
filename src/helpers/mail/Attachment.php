<?php

  /*
   * To change this license header, choose License Headers in Project Properties.
   * To change this template file, choose Tools | Templates
   * and open the template in the editor.
   */
  
  namespace PF\helpers\mail;

  /**
   * Description of MailerAttachment
   *
   * @author    Pavel Filípek <www.filipek-czech.cz>
   * @copyright © 2016, Proclient s.r.o.
   * @created   05.10.2016
   */
  class Attachment {
    private $_PATH;
    private $_NAME;
    private $_DATA;
    private $_CONTENTTYPE;

    /**
     * Vytvoření přílohy pro odeslání v emailu.
     *
     * @param string $name
     * @param string $contentType typ souboru
     * @param string $path cesta k souboru
     * @param string $data soubor v binarnim formatu
     */
    public function __construct($name, $contentType, $path = '', $data = NULL) {
      $this->_DATA = $data;
      $this->_PATH = $path;
      $this->_NAME = $name;
      $this->_CONTENTTYPE = $contentType;
    }

    public function getPath() {
      return $this->_PATH;
    }

    public function getName() {
      return $this->_NAME;
    }

    public function getData() {
      return $this->_DATA;
    }

    public function getContentType() {
      return $this->_CONTENTTYPE;
    }
  }