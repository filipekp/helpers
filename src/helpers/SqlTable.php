<?php
  
  namespace PF\helpers;

  /**
   * Reprezentuje tabulku pro sql dotazy - tedy nazev tabulky s pripadnymi joiny.
   *
   * @author Pavel Filípek <pavel@filipek-czech.cz>
   * @copyright (c) 2017, Proclient s.r.o.
   * @created 24.01.2017
   */
  class SqlTable {
    const TYPE_SIMPLE = 1;      // jednoducha tabulka -> obsahuje pouze nazev tabulky a jeji alias
    const TYPE_JOINED = 2;      // tabulka tvorena SQL JOINy

    const ALIAS_PREFIX = 't';
    private static $TABLES_COUNT = 1;

    private static $PRREFIX = '';
    protected static $useAliases = [];

    protected $sqlTableAlias = NULL;
    protected $sqlTableName;
    protected $sqlTableType;

    protected $statement = [
      'query'    => '',
      'bindings' => []
    ];

    /** @var SqlTable[] */
    protected $composition = [];

    /**
     * Vygeneruje alias tabulky a ulozi jej do property.<br />
     * Existuje-li alias, a neni-li zapnuty prepinac pro vynuceni noveho klice, vrati jej, neexistuje-li, vytvori novy.
     *
     * @return string
     */
    public function generateAlias() {
      if (is_null($this->sqlTableAlias)) {
        $this->setAlias(self::ALIAS_PREFIX . self::$TABLES_COUNT);
        self::$TABLES_COUNT++;
      }

      return $this->sqlTableAlias;
    }

    private function setAlias($alias) {
      if (is_null($this->sqlTableAlias)) {
        $currentAlias = $alias;
        $counter = 1;

        while (in_array($currentAlias, self::$useAliases)) {
          $currentAlias = $alias . '_' . $counter++;
        }

        $this->sqlTableAlias = $currentAlias;
        self::$useAliases[]  = $this->sqlTableAlias;

        $this->statement['query'] = rtrim($this->statement['query'], PHP_EOL);
        $this->statement['query'] .= ' AS ' . $this->sqlTableAlias . PHP_EOL;
      }

      return $this;
    }

    /**
     * Vytvori novou tabulku.
     * @param string $table
     */
    public function __construct($table) {
      $this->sqlTableType = self::TYPE_SIMPLE;
      $this->sqlTableName = $table;

      if (preg_match('/^[a-zA-Z_]\w*$/', trim($table))) {
        $this->statement['query'] = self::$PRREFIX . "{$this->sqlTableName}" . PHP_EOL;
      } else {
        $this->statement['query'] = "({$this->sqlTableName})" . PHP_EOL;
      }
    }

    /**
     * Zjisti, zda je tabulka slozena z nekolika spojenych.
     * @return bool
     */
    public function isJoined() {
      return $this->sqlTableType == self::TYPE_JOINED;
    }

    /**
     * Pripoji k tabulce jinou pres konstrukt JOIN.
     *
     * @param SqlTable $table
     * @param SqlFilter $sqlFilter
     *
     * @return SqlTable
     * @throws \Exception
     */
    public function join(SqlTable $table, SqlFilter $sqlFilter = NULL) {
      if ($table->isJoined()) {
        throw new \Exception('Není možné připojit tabulku vzniklou konstruktem JOIN.');
      }

      // pridani aliasu pokud jeste nebyl generovan
      $this->generateAlias();
      $table->generateAlias();

      if (is_null($sqlFilter)) {
        $this->statement['query']   .= "JOIN {$table}\n";
      } else {
        $filter = $sqlFilter->resultFilter();

        // vzhledem k tomu, ze JOINovana tabulka musi byt jednoducha (bez JOINu), staci pouzit jeji metodu __toString
        $this->statement['query']   .= "JOIN {$table} ON {$filter['where']}\n";
        $this->statement['bindings'] = array_merge($this->statement['bindings'], $filter['bindings']);
      }

      $this->sqlTableType = self::TYPE_JOINED;

      return $this;
    }

    /**
     * Pripoji k tabulce jinou pres ruzne typy JOINu.
     *
     * @param string $join
     * @param SqlTable $table
     * @param SqlFilter $sqlFilter
     *
     * @return SqlTable
     * @throws \Exception
     */
    protected function otherJoin($join, SqlTable $table, SqlFilter $sqlFilter = NULL) {
      // pridani aliasu pokud jeste nebyl generovan
      $this->generateAlias();
      $table->generateAlias();

      $oldQuery = $this->statement['query'];
      $this->statement['query'] .= $join;

      try {
        $this->join($table, $sqlFilter);
      } catch (\Exception $exception) {
        $this->statement['query'] = $oldQuery;

        throw $exception;
      }

      return $this;
    }

    /**
     * Pripoji k tabulce jinou pres konstrukt LEFT JOIN.
     *
     * @param SqlTable $table
     * @param SqlFilter $sqlFilter
     *
     * @return SqlTable
     */
    public function leftJoin(SqlTable $table, SqlFilter $sqlFilter = NULL) {
      return $this->otherJoin("LEFT ", $table, $sqlFilter);
    }

    /**
     * Pripoji k tabulce jinou pres konstrukt LEFT OUTER JOIN.
     *
     * @param SqlTable $table
     * @param SqlFilter $sqlFilter
     *
     * @return SqlTable
     */
    public function leftOuterJoin(SqlTable $table, SqlFilter $sqlFilter = NULL) {
      return $this->otherJoin("LEFT OUTER ", $table, $sqlFilter);
    }

    /**
     * Pripoji k tabulce jinou pres konstrukt RIGHT JOIN.
     *
     * @param SqlTable $table
     * @param SqlFilter $sqlFilter
     *
     * @return SqlTable
     */
    public function rightJoin(SqlTable $table, SqlFilter $sqlFilter = NULL) {
      return $this->otherJoin("RIGHT ", $table, $sqlFilter);
    }

    /**
     * Pripoji k tabulce jinou pres konstrukt RIGHT OUTER JOIN.
     *
     * @param SqlTable $table
     * @param SqlFilter $sqlFilter
     *
     * @return SqlTable
     */
    public function rightOuterJoin(SqlTable $table, SqlFilter $sqlFilter = NULL) {
      return $this->otherJoin("RIGHT OUTER ", $table, $sqlFilter);
    }

    /**
     * Pripoji k tabulce jinou pres konstrukt INNER JOIN.
     *
     * @param SqlTable $table
     * @param SqlFilter $sqlFilter
     *
     * @return SqlTable
     */
    public function innerJoin(SqlTable $table, SqlFilter $sqlFilter = NULL) {
      return $this->otherJoin("INNER ", $table, $sqlFilter);
    }

    /**
     * Pripoji k tabulce jinou pres konstrukt RIGHT INNER JOIN.
     *
     * @param SqlTable $table
     * @param SqlFilter $sqlFilter
     *
     * @return SqlTable
     */
    public function rightInnerJoin(SqlTable $table, SqlFilter $sqlFilter = NULL) {
      return $this->otherJoin("RIGHT INNER ", $table, $sqlFilter);
    }

    /**
     * Pripoji k tabulce jinou pres konstrukt LEFT INNER JOIN.
     *
     * @param SqlTable $table
     * @param SqlFilter $sqlFilter
     *
     * @return SqlTable
     */
    public function leftInnerJoin(SqlTable $table, SqlFilter $sqlFilter = NULL) {
      return $this->otherJoin("LEFT INNER ", $table, $sqlFilter);
    }

    /**
     * Vrati sloupec tabulky -> s jejim aliasem.
     *
     * @param string $column
     * @return string
     */
    public function column($column) {
      return "{$this->generateAlias()}.`{$column}`";
    }

    /**
     * Vrati wildcard pro vsechny sloupce tabulky -> s jejim aliasem.
     * @return string
     */
    public function columnsAll() {
      return "{$this->generateAlias()}.*";
    }

    /**
     * Getter.
     * @return string
     */
    public function getName() {
      return $this->sqlTableName;
    }

    /**
     * Getter.
     * @return string
     */
    public function getFullName() {
      return self::$PRREFIX . $this->sqlTableName;
    }

    /**
     * Getter.
     * @return int
     */
    public function getType() {
      return $this->sqlTableType;
    }

    /**
     * Vrati kompletni statement tabulky.<br />
     * Ve tvaru:<br />
     * [<br />
     *    'query'    => 'dotaz na tabulku - jednoduchou (nazev), nebo join - obsahujici vazane promenne',<br />
     *    'bindings' => ['hodnota_vazane_promenne_1', 'hodnota_vazane_promenne_2', ...]<br />
     * ]
     *
     * @return array
     */
    public function statement() {
      return $this->statement;
    }

    public static function setPrefix($prefix = DB_PREFIX) {
      self::$PRREFIX = $prefix;
    }

    /**
     * Vytvoří instanci tabulky.
     *
     * @param string      $table
     * @param string|NULL $alias
     *
     * @return SqlTable
     */
    public static function create($table, $alias = NULL) {
      $sqlTable = new SqlTable($table);

      if (!is_null($alias)) {
        $sqlTable->setAlias($alias);
      }

      return $sqlTable;
    }
  
    /**
     * Převede pole sloupců do stringu pro dotaz.
     *
     * @param $columns     *
     * @return string
     */
    public static function convertColumns($columns) {
      $privColumns = $columns;
      array_walk($privColumns, function(&$item, $key) {
        $item = $item . ((is_numeric($key)) ? '' : ' AS ' . $key);
      });
      return implode(",\n", $privColumns);
    }

    /**
     * Rychly pristup ke sloupcum.<br />
     * __get je volana pouze pokud property neexistuje nebo je neviditelna, a takove property nejsou k dispozici
     *    => je-li volana neexistujici nebo nepristupna property, jedna se o sloupec tabulky
     *
     * @param $name
     * @return string
     */
    public function __get($name) {
      return $this->column($name);
    }

    /**
     * Magicka metoda pro pouziti tridy jako textovy retezec.<br />
     * MA MOUCHY, POUZE PRO DEBUG.
     *
     * @return string
     */
    public function __toString() {
      return vsprintf(str_replace('?', '%s', $this->statement['query']), array_map(function($value) {
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
      }, $this->statement['bindings']));
    }
  }
