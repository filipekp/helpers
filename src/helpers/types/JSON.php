<?php
  
  namespace PF\helpers\types;
  
  /**
   * Objekt reprezentujici datovy typ JSON.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   19.03.2019
   */
  class JSON extends \ArrayObject implements \JsonSerializable {
    protected $_json;
  
    public function __construct($json) {
      if (!is_array($json)) {
        $json = ((($decoded = @json_decode($json, TRUE))) ? $decoded : array());
      }
    
      $this->_json = (($json) ? $json : array());
    }
  
    public function jsonSerialize() {
      return $this->_json;
    }
  
    public function offsetExists($offset) {
      return array_key_exists($offset, $this->_json);
    }
  
    public function offsetGet($offset) {
      return $this->_json[$offset];
    }
  
    public function offsetSet($offset, $value) {
      $this->_json[$offset] = $value;
    }
  
    public function offsetUnset($offset) {
      unset($this->_json[$offset]);
    }
  
    public function count($mode = 'COUNT_NORMAL') {
      return count($this->_json, $mode);
    }
  
    public function getIterator() {
      return new \ArrayIterator($this->_json);
    }
  
    public function __toString() {
      return json_encode($this->_json, JSON_PRETTY_PRINT);
    }
  }