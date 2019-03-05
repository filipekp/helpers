<?php
  
  namespace PF\helpers;
  
  /**
   * Třída Verifier.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   07.02.2019
   */
  class Verifier
  {
    const SALT = ']t+0MQ+A5x1BBc°;[g-G04WScL5°C9QHcCT*mA/3FwfOGJkFh/NbwkYLKMz°aD6p';
    
    private static function _getIv() {
      $ivlen = openssl_cipher_iv_length('aes-256-cbc');
      return substr(md5(self::SALT), 0, $ivlen);
    }
    
    /**
     * Ověří IP/rozsah IP případně callback na ověření.
     *
     * @param $ip
     * @param $arrayIPS
     *
     * @return bool
     */
    public static function allowedIP($ip, $arrayIPS) {
      foreach ((array)$arrayIPS as $ipItem) {
        if (is_callable($ipItem)) {
          if (call_user_func_array($ipItem, [$ip])) {
            return TRUE;
          }
        } elseif (strpos($ipItem, '-') !== FALSE) {
          $ipRange = array_map('trim', explode('-', $ipItem));
          if (ip2long($ipRange[0]) <= ip2long($ip) && ip2long($ip) <= ip2long($ipRange[1])) {
            return TRUE;
          }
        } elseif (ip2long($ipItem) == ip2long($ip)) {
          return TRUE;
        }
      }
    
      return FALSE;
    }
  
    /**
     * Funkce pro zakódování stringu.
     *
     * @param $string
     * @return string
     */
    public static function encode($string) {
      return base64_encode(
        openssl_encrypt(
          $string,
          'aes-256-cbc',
          self::SALT,
          OPENSSL_RAW_DATA,
          self::_getIv()
        )
      );
    }
  
    /**
     * Funkce pro dekódování stringu.
     *
     * @param $string
     * @return string|bool
     */
    public static function decode($string) {
      $decrypted = openssl_decrypt(
        base64_decode($string, TRUE),
        'aes-256-cbc',
        self::SALT,
        OPENSSL_RAW_DATA,
        self::_getIv()
      );
    
      return (($decrypted) ? rtrim($decrypted, '\0') : FALSE);
    }
  }