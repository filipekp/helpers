<?php
  
  namespace PF\helpers;
  
  /**
   * Třída General.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   07.02.2019
   */
  class General
  {
    /**
     * Zkontroluje, zda je objekt pozadovaneho typu.
     *
     * @param object $object
     * @param string $type
     *
     * @return bool
     */
    public static function isType($object, $type) {
      switch ($type) {
        case 'string':
          return is_string($object);
        case 'int':
        case 'integer':
          return is_int($object);
        case 'float':
        case 'double':
          return is_float($object);
        case 'bool':
        case 'boolean':
          return is_bool($object);
        
        default:
          return is_a($object, $type);
      }
    }
    
    /**
     * Přetypuje objekt na dyný typ.
     *
     * @param mixed  $object
     * @param string $type
     *
     * @return array|bool|\DateTime|float|int|object|string
     * @throws \Exception
     */
    public static function retype($object, $type) {
      switch (strtolower($type)) {
        case 'datetime':
          return ((is_string($object)) ? new \DateTime(date('Y-m-d H:i:s', strtotime($object))) : new \DateTime());
        case 'int':
        case 'integer':
          return (int)$object;
        case 'float':
        case 'double':
        case 'real':
          return (float)((is_string($object)) ? str_replace(',', '.', $object) : $object);
        case 'bool':
        case 'boolean':
          return (bool)$object;
        case 'array':
          return (array)$object;
        case 'object':
          return (object)$object;
        case 'null':
          unset($object);
          
          return NULL;
        
        default:
          return (string)$object;
      }
    }
    
    /**
     * Ziska parametry name pro inputy.
     *
     * @param array  $array
     * @param array  $inputsArr
     * @param string $keyOld
     */
    public static function getNameRecursive(array $array, array &$inputsArr, $keyOld = '') {
      foreach ($array as $key => $value) {
        if (is_array($value)) {
          $newKey = $keyOld . '[' . $key . ']';
          self::getNameRecursive($value, $inputsArr, $newKey);
        } else {
          $inputsArr[$keyOld . '[' . $key . ']'] = $value;
        }
      }
    }
    
    /**
     * Rekurzivne vygeneruje hidden inputy.
     *
     * @param array $array
     *
     * @return array
     */
    public static function generateHiddenInputs($array) {
      $hiddenInput = function ($key, $value) {
        return '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
      };
      
      $inputsArr = [];
      
      foreach ($array as $key => $value) {
        if (is_array($value)) {
          self::getNameRecursive($value, $inputsArr, $key);
        } else {
          $inputsArr[$key] = $value;
        }
      }
      
      $getParamsInputs = [];
      foreach ($inputsArr as $key => $value) {
        $getParamsInputs[] = $hiddenInput($key, $value);
      }
      
      return $getParamsInputs;
    }
  
    /**
     * Funkce pro zjisteni mime-type souboru nebo bufferu
     *
     * @param mixed  $content
     * @param string $type options: file (default), buffer
     *
     * @return string
     */
    public static function getMimeType($content, $type = 'file') {
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      switch ($type) {
        case 'buffer':
          $mimeType = finfo_buffer($finfo, $content);
          break;
      
        case 'file':
        default:
          $mimeType = finfo_file($finfo, $content);
          break;
      }
      finfo_close($finfo);
    
      return $mimeType;
    }
  
    /**
     * Vyfiltruje konstanty třídy podle prefixu.
     *
     * @param string $class
     * @param string $prefix
     *
     * @return array
     * @throws \ReflectionException
     */
    public static function getConstantsByPrefix($class, $prefix) {
      $reflection = new \ReflectionClass($class);
      $constants = $reflection->getConstants();
    
      return MyArray::init($constants)->filterByPrefix($prefix);
    }
  
    /**
     * Zjisti, zda je pozadovana hodnota mezi konstantami s danym prefixem.
     *
     * @param mixed  $value
     * @param string $class
     * @param string $prefix
     *
     * @return bool
     * @throws \ReflectionException
     */
    public static function isConstantByPrefix($value, $class, $prefix) {
      return in_array($value, self::getConstantsByPrefix($class, $prefix));
    }
  
    /**
     * Funkce pro rekuzivni implode
     *
     * @param string $glue
     * @param array  $array
     *
     * @return string
     */
    public static function implodeRecursive($glue, array $array) {
      $ret = '';
    
      foreach ($array as $item) {
        if (is_array($item)) {
          $ret .= self::implodeRecursive($glue, $item) . $glue;
        } else {
          $ret .= $item . $glue;
        }
      }
    
      $ret = substr($ret, 0, 0-strlen($glue));
    
      return $ret;
    }
  
    /**
     * Stáhne obsah URL.
     *
     * @return string The function returns the read data or false on failure.
     * @throws \Exception
     */
    public static function urlGetContents($url, $flags = null, $context = null, $offset = null, $maxlen = null) {
      $getHttpResponseCode = function($url) use ($context) {
        stream_context_set_default($context);
      
        $headers = get_headers($url, 1);
        return substr($headers[0], 9, 3);
      };
    
      if (($responseCode = $getHttpResponseCode($url) != '200')) {
        throw new \Exception("Požadovaná url '{$url}' neexistuje nebo je nedostupná.", $responseCode);
      } else {
        return file_get_contents($url, $flags);
      }
    }
  
    /**
     * Vygeneruje náhodný token.
     *
     * @param int $length
     * @return string
     */
    public static function token($length = 32) {
      // Create random token
      $string = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
      $max = strlen($string) - 1;
    
      $token = '';
      for ($i = 0; $i < $length; $i++) {
        $token .= $string[mt_rand(0, $max)];
      }
    
      return $token;
    }
  }