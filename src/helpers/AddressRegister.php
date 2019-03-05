<?php
  
  namespace PF\helpers;
  
  use PF\helpers\exceptions\RegInvalidArgumentException;
  use voku\helper\HtmlDomParser;

  /**
   * Třída AddressRegister.
   *
   * @author    Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright © 2019, Proclient s.r.o.
   * @created   27.02.2019
   */
  class AddressRegister
  {
    private $base_url = 'https://vdp.cuzk.cz/vdp/ruian/overeniadresy/vyhledej?';
    
    /**
     * Checks if address is valid - exists and is unique. If it is, returns all data about the address.
     *
     * @param array address fields and values
     *
     * @return false|array FALSE if address is not valid, else ARRAY with information
     * @throws RegInvalidArgumentException Input array does not contain necessary fields.
     */
    public function isAddressValid($input) {
      $input = $this->checkAndInitInput($input);
      $url   = $this->createGetQuery($input);
      // get result from server
      $html = HtmlDomParser::file_get_html($url);
      
      // process response
      return $this->processResponse($html);
    }
  
    /**
     * @param HtmlDomParser $html
     *
     * @return array|bool
     */
    private function processResponse($html) {
      $total_records = $html->find('div.dataPagerRight', 0)->find('b', 0)->innertext;
      $total_number  = intval(preg_replace('/[^0-9]/', '', $total_records));
      // nothing found or multiple records
      if ($total_number != 1) {
        return FALSE;
      }
      // get all data about the address
      $item                           = $html->find('#item tbody tr', 0);
      
      $output                         = [];
      $output['town_district']        = html_entity_decode($item->children(0)->innertext, NULL, 'UTF-8');
      $output['house_number']         = html_entity_decode($item->children(1)->innertext, NULL, 'UTF-8');
      $output['street_name']          = html_entity_decode($item->children(2)->innertext, NULL, 'UTF-8');
      $output['orientational_number'] = $item->children(3)->innertext;
      $output['zip_code']             = $item->children(4)->innertext;
      $output['post_name']            = html_entity_decode($item->children(5)->innertext, NULL, 'UTF-8');
      $output['town_name']            = html_entity_decode(trim(strstr($item->children(6)->innertext, '(', TRUE)), NULL, 'UTF-8');
      
      return $output;
    }
    
    private function createGetQuery($input) {
      // prepare data for GET
      $data  = [
        'as.nazevUl'                   => $input['street_name'],
        'as.cisDom'                    => $input['house_number'],
        'as.cisOr.cisloOrientacniText' => $input['orientational_number'],
        'as.nazevCo'                   => $input['town_district'],
        'as.nazevOb'                   => $input['town_name'],
        'as.psc'                       => $input['zip_code'],
        'asg.sort'                     => 'UZEMI',
        'search'                       => 'Vyhledat'
      ];
      $query = http_build_query($data);
      
      return $this->base_url . $query;
    }
    
    private function checkAndInitInput($input) {
      // check input array
      if (((array_key_exists('town_district', $input) && !empty($input['town_district'])) ||
          (array_key_exists('town_name', $input) && !empty($input['town_name']))) &&
          ((array_key_exists('orientational_number', $input) && !empty($input['orientational_number'])) ||
          (array_key_exists('house_number', $input) && !empty($input['house_number'])))) {
      } else {
        throw new RegInvalidArgumentException('You must enter at least town district or town name AND house number or orientational number.');
      }
      // init input array
      $fields = ['street_name', 'house_number', 'orientational_number', 'town_district', 'town_name', 'zip_code'];
      foreach ($fields as $field) {
        if (!array_key_exists($field, $input)) {
          $input[$field] = '';
        }
      }
      
      return $input;
    }
  }