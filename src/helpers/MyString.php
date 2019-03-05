<?php
  
  namespace PF\helpers;
  
  /**
   * Třída MyString pro práci s řetězci.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   07.02.2019
   */
  class MyString
  {
    /**
     * Ořízne řetězec na danou délku po celých slovech.
     *
     * @param string $string
     * @param int    $limit
     *
     * @return string
     */
    public static function trimEntireWords($string, $limit = 40) {
      // return string when length is < then limit
      if (mb_strlen($string, 'UTF-8') < $limit) {
        return $string;
      }
    
      $regex = '/(.{0, ' . $limit . '})\b/';
      $matches = array('', '');
    
      if (preg_match($regex, $string, $matches)) {
        return $matches[1] . ' ...';
      } else {
        return mb_substr($string, 0, $limit, 'UTF-8');
      }
    }
  
    /**
     * Zjistí, zda řetězec začínám požadovaným subřetězcem.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function startsWith($haystack, $needle) {
      return strpos($haystack, $needle) === 0;
    }
  
    /**
     * Zjistí, zda řetězec končí požadovaným subřetězcem.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function endsWith($haystack, $needle) {
      return strpos($haystack, $needle) === (strlen($haystack) - strlen($needle));
    }
  
    /**
     * Odstrani diakritiku z textu
     *
     * @param string $text
     *
     * @return string
     */
    public static function removeDiacritics($text) {
      // remove diacritics
      $conversionTable = Array(
        'ä'=>'a', 'Ä'=>'A', 'á'=>'a', 'Á'=>'A', 'à'=>'a', 'À'=>'A', 'ã'=>'a', 'Ã'=>'A', 'â'=>'a', 'Â'=>'A', 'ą'=>'a', 'Ą'=>'A', 'ă'=>'a', 'Ă'=>'A',
        'č'=>'c', 'Č'=>'C', 'ć'=>'c', 'Ć'=>'C', 'ç'=>'c', 'Ç'=>'C',
        'ď'=>'d', 'Ď'=>'D', 'đ'=>'d', 'Đ'=>'D',
        'ě'=>'e', 'Ě'=>'E', 'é'=>'e', 'É'=>'E', 'ë'=>'e', 'Ë'=>'E', 'è'=>'e', 'È'=>'E', 'ê'=>'e', 'Ê'=>'E', 'ę'=>'e', 'Ę'=>'E',
        'í'=>'i', 'Í'=>'I', 'ï'=>'i', 'Ï'=>'I', 'ì'=>'i', 'Ì'=>'I', 'î'=>'i', 'Î'=>'I',
        'ľ'=>'l', 'Ľ'=>'L', 'ĺ'=>'l', 'Ĺ'=>'L', 'ł'=>'l', 'Ł'=>'L',
        'ń'=>'n', 'Ń'=>'N', 'ň'=>'n', 'Ň'=>'N', 'ñ'=>'n', 'Ñ'=>'N',
        'ó'=>'o', 'Ó'=>'O', 'ö'=>'o', 'Ö'=>'O', 'ô'=>'o', 'Ô'=>'O', 'ò'=>'o', 'Ò'=>'O', 'õ'=>'o', 'Õ'=>'O', 'ő'=>'o', 'Ő'=>'O',
        'ř'=>'r', 'Ř'=>'R', 'ŕ'=>'r', 'Ŕ'=>'R',
        'š'=>'s', 'Š'=>'S', 'ś'=>'s', 'Ś'=>'S', 'ş'=>'s', 'Ş'=>'S',
        'ť'=>'t', 'Ť'=>'T', 'ţ'=>'t', 'Ţ'=>'T',
        'ú'=>'u', 'Ú'=>'U', 'ů'=>'u', 'Ů'=>'U', 'ü'=>'u', 'Ü'=>'U', 'ù'=>'u', 'Ù'=>'U', 'ũ'=>'u', 'Ũ'=>'U', 'û'=>'u', 'Û'=>'U', 'ű'=>'u', 'Ű'=>'U',
        'ý'=>'y', 'Ý'=>'Y',
        'ž'=>'z', 'Ž'=>'Z', 'ź'=>'z', 'Ź'=>'Z', 'ż'=>'z', 'Ż'=>'Z'
      );
    
      return strtr($text, $conversionTable);
    }
  
    /**
     * Převede řetězec na malá písmena a speciální znaky nahradí oddělovačem.
     *
     * @param string $text
     * @param string $delimiter
     *
     * @return string
     */
    public static function seoTypeConversion($text, $delimiter = '-') {
      // convert to lower
      $lower = strtolower(self::removeDiacritics($text));
    
      // replace everything with dashes - excluding upper letters, lower letters, numbers and dashes
      $pattern = '/[^0-9a-zA-Z' . $delimiter . ']/';
      $multipleDelimiters = preg_replace($pattern, $delimiter, $lower);
    
      // replace multiple dashes to one dash
      $seo = preg_replace('/' . str_replace('.', '\\.', $delimiter) . '+/', $delimiter, $multipleDelimiters);
    
      // return dash trimmed seo
      return trim($seo, $delimiter);
    }
  
    /**
     * Upraví http query string v odkaze.<br />
     * <strong>Pozor:</strong> je-li link pouze query string, musí začínat otazníkem.
     *
     * @param string $link
     * @param array  $values
     * @param bool   $removeEmpty
     *
     * @return string
     *
     */
    public static function modifyHttpQuery($link, array $values = array(), $removeEmpty = FALSE) {
      $link = self::parseUrl($link);
      if (!array_key_exists('query', $link)) {
        $link['query'] = [];
      }
    
      // upravi parametry
      $link['query'] = $values + $link['query'];
    
      if ($removeEmpty) {
        $link['query'] = array_filter($link['query']);
      }
    
      // vygeneruje nove url
      $url = '';
      $url .= ((array_key_exists('scheme', $link)) ? $link['scheme'] . '://' : '');
      $url .= self::item($link, 'host', '');
      $url .= ((array_key_exists('port', $link)) ? $link['port'] . ':' : '');
      $url .= self::item($link, 'path', '');
      $url .= (($link['query']) ? '?' . http_build_query($link['query']) : '');
    
      return $url;
    }
    
    /**
     * Rozparsuje odkaz a query.
     *
     * @param string $link
     * @return array
     */
    public static function parseUrl($link) {
      $parsedLink = parse_url(str_replace('&amp;', '&', $link));
      parse_str(\Prosys::item($parsedLink, 'query', ''), $parsedLink['query']);
    
      return $parsedLink;
    }
  
    /**
     * Vygeneruje náhodný řetězec pro zadanou délku.
     *
     * @param int $length
     *
     * @return string
     */
    public static function randomString($length = 10) {
      // string pro generovani
      $pool = '0123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ';
      // zde bude výsledný řetězec uložen
      $resString = '';
      for ($i = 0; $i < $length; $i++) {
        $resString .= substr($pool, mt_rand(0, strlen($pool) - 1), 1);
      }
    
      return $resString;
    }
  
    /**
     * Funkce pro skoloňování slov podle čísla.
     *
     * @param float  $number
     * @param string $allOther  výraz pro hodnotu vetší než 5 nebo rovno 0
     * @param string $once      výraz pro hodnotu rovno 1
     * @param string $oneToFive výraz pro hodnotu vetší než 1 nebo menší než 5
     * @param string $float     výraz pro desetinná čísla
     * @param string $delimiter oddelovac cisla a jednotky
     *
     * @return string '$number word form' => '1 hrnek'
     */
    public static function inflection($number, $allOther, $once, $oneToFive, $float, $delimiter = '&nbsp;') {
      $string = $number . $delimiter;
      if ((int)$number != $number) {
        $string .= $float;
      } elseif ($number == 0 OR $number >= 5) {
        $string .= $allOther;
      } elseif ($number == 1) {
        $string .= $once;
      } elseif ($number < 5) {
        $string .= $oneToFive;
      }
    
      return $string;
    }
  
    /**
     * Odmaskuje retezec podle predaneho cisla a data.
     *
     * @param string  $mask
     * @param integer $number
     * @param string $date optional
     *
     * @return string
     */
    public static function unmaskString($mask, $number, $date = '') {
      $time      = (($date) ? strtotime($date) : time());
      $maskArray = [];
      preg_match_all('/(\{.*?\})/', $mask, $maskArray);
    
      foreach ($maskArray[1] as $value) {
        $valueReal = preg_replace('/{(.*)}/', '$1', $value);
      
        if (strpos($valueReal, '#') !== FALSE) {
          $valueReal = sprintf('%0' . strlen($valueReal) . 'd', $number);
        } else {
          if (strpos($valueReal, 'Y') !== FALSE) {
            switch ($valueReal) {
              case 'YYYY':
                $valueReal = date('Y', $time);
                break;
            
              case 'YYY':
                $valueReal = substr(date('Y', $time), 0, 1) . substr(date('Y', $time), -2);
                break;
            
              case 'Y':
              default:
                $valueReal = date('y', $time);
                break;
            }
          }
        }
      
        $mask = str_replace($value, $valueReal, $mask);
      }
    
      return $mask;
    }
  
    /**
     * Ořeže html text na danou délku po celých slovech a přidá postfix.
     *
     * @param string $html
     * @param int    $length
     * @param string $postfix
     *
     * @return bool|mixed|string
     */
    public static function truncate($html, $length, $postfix = '&nbsp;...') {
      if (mb_strlen(strip_tags($html)) <= $length) {
        return $html;
      }
    
      $total = mb_strlen($postfix);
      $open_tags = array();
      $return = '';
      $finished = false;
      $final_segment = '';
      $self_closing_elements = array(
        'area',
        'base',
        'br',
        'col',
        'frame',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param'
      );
    
      $inline_containers = array(
        'a',
        'b',
        'abbr',
        'cite',
        'em',
        'i',
        'kbd',
        'span',
        'strong',
        'sub',
        'sup'
      );
    
      while (!$finished) {
        if (preg_match('/^<(\w+)[^>]*>/u', $html, $matches)) {
          if (!in_array($matches[1], $self_closing_elements)) {
            $open_tags[] = $matches[1];
          }
          $html = substr_replace($html, '', 0, strlen($matches[0]));
          $return .= $matches[0];
        } elseif (preg_match('/^<\/(\w+)>/u', $html, $matches)) {
          $key = array_search($matches[1], $open_tags);
          if ($key !== false) {
            unset($open_tags[$key]);
          }
          $html = substr_replace($html, '', 0, strlen($matches[0]));
          $return .= $matches[0];
        } else {
          if (preg_match('/^([^<]+)(<\/?(\w+)[^>]*>)?/u', $html, $matches)) {
            $segment = $matches[1];
            $segment_length = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $segment));
            if ($segment_length + $total > $length) { // Truncate $segment and set as $final_segment:
              $remainder = $length - $total;
              $entities_length = 0;
              if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/ui', $segment, $entities, PREG_OFFSET_CAPTURE)) {
                foreach($entities[0] as $entity) {
                  if ($entity[1] + 1 - $entities_length <= $remainder) {
                    $remainder--;
                    $entities_length += mb_strlen($entity[0]);
                  } else {
                    break;
                  }
                }
              }
              $finished = true;
              $final_segment = mb_substr($segment, 0, $remainder + $entities_length);
            } else {
              $return .= $segment;
              $total += $segment_length;
              $html = substr_replace($html, '', 0, strlen($segment));
            }
          } else {
            $finshed = true;
          }
        }
      }
      if (strpos($final_segment, ' ') === false && preg_match('/<(\w+)[^>]*>$/u', $return)) {
        $return = preg_replace('/<(\w+)[^>]*>$/u', '', $return);
        $key = array_search($matches[3], $open_tags);
        if ($key !== false) {
          unset($open_tags[$key]);
        }
      } else {
        $return .= mb_substr($final_segment, 0, mb_strrpos($final_segment, ' '));
      }
    
      $return = trim($return);
      
      $closing_tags = array_reverse($open_tags);
      $ending_added = false;
      foreach($closing_tags as $tag) {
        if (!in_array($tag, $inline_containers) && !$ending_added) {
          $return .= $postfix;
          $ending_added = true;
        }
        $return .= '</' . $tag . '>';
      }
    
      $return .= ((!$ending_added) ? $postfix : '');
    
      return $return;
    }
  
    /**
     * Převede string camelcase na underscore.
     *
     * @param $word
     * @return mixed
     */
    public static function decamelize($word) {
      return preg_replace_callback(
        '/(^|[a-z])([A-Z])/',
        function ($matches) { return strtolower((strlen($matches[1]) ? "{$matches[1]}_{$matches[2]}" : "{$matches[2]}")); },
        $word
      );
    }
  
    /**
     * Převede string underscore na camelcase.
     *
     * @param $word
     * @return mixed
     */
    public static function camelize($word) {
      return preg_replace_callback('/(^|_)([a-z])/', function($matches) { return strtoupper($matches[2]); }, $word);
    }
  
    /**
     * Multibyte alternativa ucfirst.
     *
     * @param string      $string
     * @param string|NULL $encoding
     *
     * @return string
     */
    public static function mb_ucfirst($string, $encoding = NULL) {
      if (is_null($encoding)) {
        $encoding = mb_internal_encoding();
      }
    
      return mb_strtoupper(mb_substr($string, 0, 1, $encoding), $encoding) . mb_substr($string, 1, NULL, $encoding);
    }
  
    /**
     * Multibyte alternativa lcfirst.
     *
     * @param string      $string
     * @param string|NULL $encoding
     *
     * @return string
     */
    public static function mb_lcfirst($string, $encoding = NULL) {
      if (is_null($encoding)) {
        $encoding = mb_internal_encoding();
      }
    
      return mb_strtolower(mb_substr($string, 0, 1, $encoding), $encoding) . mb_substr($string, 1, NULL, $encoding);
    }
  }