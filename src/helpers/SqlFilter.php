<?php
  
  namespace PF\helpers;


  /**
   * Represents SQL filter - means WHERE condition.
   *
   * @author Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright (c) 2017, Proclient s.r.o.
   */
  class SqlFilter
  {
    private $_filter = array('where' => '', 'bindings' => array());

    /**
     * Creates new filter.
     *
     * @return SqlFilter
     */
    public static function create() {
      return new self();
    }

    /**
     * Quotes the database element with `
     *
     * @param type $element
     * @return type
     */
    private function quote($element) {
      return ((strpos($element, '`') === FALSE) ? "{$element}" : $element);
    }

    /**
     * Creates tautology condition.
     *
     * @return SqlFilter
     */
    public function identity() {
      $this->_filter['where'] .= '1';

      return $this;
    }

    /**
     * Create contradiction condition.
     *
     * @return SqlFilter
     */
    public function contradiction() {
      $this->_filter['where'] .= '0 = 1';

      return $this;
    }
  
    /**
     * Creates comparison condition of WHERE.
     *
     * @param string $element
     * @param string $cmp
     * @param string $value
     *
     * @return SqlFilter
     * @throws \Exception
     */
    public function compare($element, $cmp, $value) {
      $validComparators = ['<', '>', '<>', '<=', '>=', '='];
      if (!in_array($cmp, $validComparators)) {
        throw new \Exception('Argument `cmp` (comparator) has not valid value. Allowed values: ' . implode(', ', $validComparators) . '.');
      }
      
      $this->_filter['where'] .= "{$this->quote($element)} {$cmp} ?";
      $this->_filter['bindings'][] = $value;

      return $this;
    }

    /**
     * Creates comparison columns condition of WHERE.
     *
     * @param string $element
     * @param string $cmp
     * @param string $value
     *
     * @return SqlFilter
     */
    public function compareColumns($element1, $cmp, $element2) {
      $this->_filter['where'] .= "{$this->quote($element1)} {$cmp} {$this->quote($element2)}";

      return $this;
    }

    /**
     * Checks if element IS NULL.
     *
     * @param string $element
     * @return SqlFilter
     */
    public function isEmpty($element) {
      $this->_filter['where'] .= "{$this->quote($element)} IS NULL";

      return $this;
    }

    /**
     * Checks if element IS NOT NULL.
     *
     * @param string $element
     * @return SqlFilter
     */
    public function isNotEmpty($element) {
      $this->_filter['where'] .= "{$this->quote($element)} IS NOT NULL";

      return $this;
    }

    /**
     * Checks if element value contains given string.
     *
     * @param string $element
     * @param string $string
     *
     * @return SqlFilter
     */
    public function contains($element, $string) {
      $this->_filter['where'] .= "{$this->quote($element)} LIKE ?";
      $this->_filter['bindings'][] = "%{$string}%";

      return $this;
    }

    /**
     * Checks if element value starts with given string.
     *
     * @param string $element
     * @param string $string
     *
     * @return SqlFilter
     */
    public function startWith($element, $string) {
      $this->_filter['where'] .= "{$this->quote($element)} LIKE ?";
      $this->_filter['bindings'][] = "{$string}%";

      return $this;
    }

    /**
     * Checks if element value starts with given string.
     *
     * @param string $element
     * @param string $string
     *
     * @return SqlFilter
     */
    public function endWith($element, $string) {
      $this->_filter['where'] .= "{$this->quote($element)} LIKE ?";
      $this->_filter['bindings'][] = "%{$string}";

      return $this;
    }

    /**
     * Checks if element is between value range.
     *
     * @param string $element
     * @param mixed $from
     * @param mixed $to
     */
    public function between($element, $from, $to) {
      $this->_filter['where'] .= "{$this->quote($element)} BETWEEN ? AND ?";
      $this->_filter['bindings'] = array_merge($this->_filter['bindings'], array($from, $to));

      return $this;
    }

    /**
     * Checks if element value is IN the array of values.
     *
     * @param string $element
     * @param array $array
     *
     * @return SqlFilter
     */
    public function inArray($element, array $array) {
      if ($array) {
        $this->_filter['where'] .= "{$this->quote($element)} IN (" . implode(', ', array_fill(0, count($array), '?')) . ")";
        $this->_filter['bindings'] = array_merge($this->_filter['bindings'], array_values($array));
      } else {
        if (substr($this->_filter['where'], -3) === 'OR ') {
          $this->contradiction();
        } else {
          $this->identity();
        }
      }

      return $this;
    }

    /**
     * Checks if element value is NOT IN the array of values.
     *
     * @param string $element
     * @param array $array
     *
     * @return SqlFilter
     */
    public function notInArray($element, array $array) {
      if ($array) {
        $this->_filter['where'] .= "{$this->quote($element)} NOT IN (" . implode(", ", array_fill(0, count($array), '?')) . ")";
        $this->_filter['bindings'] = array_merge($this->_filter['bindings'], array_values($array));
      } else {
        if (substr($this->_filter['where'], -3) === 'OR ') {
          $this->contradiction();
        } else {
          $this->identity();
        }
      }

      return $this;
    }

    /**
     * Checks if element value is IN filter subquery.
     *
     * @param string $element
     * @param SqlFilter $filter
     *
     * @return SqlFilter
     */
    public function inFilter($element, SqlFilter $filter) {
      $filter = $filter->resultFilter();

      $this->_filter['where'] .= "{$this->quote($element)} IN ({$filter['where']})";
      $this->_filter['bindings'] = array_merge($this->_filter['bindings'], $filter['bindings']);

      return $this;
    }

    /**
     * Checks if element value is NOT IN filter subquery.
     *
     * @param string $element
     * @param SqlFilter $filter
     *
     * @return SqlFilter
     */
    public function notInFilter($element, SqlFilter $filter) {
      $filter = $filter->resultFilter();

      $this->_filter['where'] .= "{$this->quote($element)} NOT IN ({$filter['where']})";
      $this->_filter['bindings'] = array_merge($this->_filter['bindings'], $filter['bindings']);

      return $this;
    }

    /**
     * Checks if filter returns any row.
     *
     * @param SqlFilter $filter
     */
    public function exists(SqlFilter $filter) {
      $filter = $filter->resultFilter();

      $this->_filter['where'] .= "EXISTS ({$filter['where']})";
      $this->_filter['bindings'] = array_merge($this->_filter['bindings'], $filter['bindings']);

      return $this;
    }

    /**
     * Checks if filter returns no row.
     *
     * @param SqlFilter $filter
     */
    public function notExists(SqlFilter $filter) {
      $filter = $filter->resultFilter();

      $this->_filter['where'] .= "NOT EXISTS ({$filter['where']})";
      $this->_filter['bindings'] = array_merge($this->_filter['bindings'], $filter['bindings']);

      return $this;
    }

    /**
     * Logical AND between subqueries.
     *
     * @param SqlFilter $filter
     * @return SqlFilter
     */
    public function andL(SqlFilter $filter = NULL) {
      if (is_null($filter)) {
        $this->_filter['where'] .= ' AND ';
      } else {
        $filter = $filter->resultFilter();

        $this->_filter['where'] = "({$this->_filter['where']}) AND ({$filter['where']})";
        $this->_filter['bindings'] = array_merge($this->_filter['bindings'], $filter['bindings']);
      }

      return $this;
    }

    /**
     * Logical OR between subqueries.
     *
     * @param SqlFilter $filter
     * @return SqlFilter
     */
    public function orL(SqlFilter $filter = NULL) {
      if (is_null($filter)) {
        $this->_filter['where'] .= ' OR ';
      } else {
        $filter = $filter->resultFilter();

        $this->_filter['where'] = "({$this->_filter['where']}) OR ({$filter['where']})";
        $this->_filter['bindings'] = array_merge($this->_filter['bindings'], $filter['bindings']);
      }

      return $this;
    }

    /**
     * Creates filter by creating SELECT subquery.
     *
     * @param string $element
     * @param SqlTable|string $collection
     * @param SqlFilter $filter
     *
     * @return SqlFilter
     */
    public function filter($element, $collection, SqlFilter $filter = NULL) {
      $element = (($element == '*') ? $element : $this->quote($element));

      if (is_a($collection, '\prosys\SqlTable')) {
        $collectionStmt = $collection->statement();

        $collection = $collectionStmt['query'];
        $this->_filter['bindings'] = array_merge($this->_filter['bindings'], $collectionStmt['bindings']);
      } else {
        $collection = $this->quote($collection);
      }

      $where = '';
      $whereBindings = array();
      if (!is_null($filter)) {
        $filter = $filter->resultFilter();

        $where = " WHERE {$filter['where']}";
        $whereBindings = $filter['bindings'];
      }

      $this->_filter['where'] = "SELECT {$element} FROM {$collection}{$where}";
      $this->_filter['bindings'] = array_merge($this->_filter['bindings'], $whereBindings);

      return $this;
    }

    /**
     * Returns result -> WHERE condition.
     *
     * @return string
     */
    public function resultFilter() {
      return (($this->_filter['where']) ? $this->_filter : ['where' => '', 'bindings' => []]);
    }

    /**
     * Magicka metoda pro pouziti tridy jako textovy retezec.<br />
     * TODO: ma mouchy, pouze pro debug.
     *
     * @return string
     */
    public function __toString() {
      return vsprintf(str_replace('?', '%s', $this->resultFilter()['where']), array_map(function($value) {
        if ($value !== 0) {
          switch ($value) {
            case 'NULL':
              $value = NULL;
            break;

            default:
              $value = "'{$value}'";
          }
        }

        return $value;
      }, $this->resultFilter()['bindings']));
    }
  }
