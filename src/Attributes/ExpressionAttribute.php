<?php namespace Sintattica\Atk\Attributes;
use Sintattica\Atk\DataGrid\DataGrid;
use Sintattica\Atk\Db\Query;


/**
 * With the atkExpressionAttribute class you can select arbitrary SQL expressions
 * like subqueries etc. It's not possible to save values using this attribute.
 *
 * @author Peter C. Verhage <peter@ibuildings.nl>
 * @package atk
 * @subpackage attributes
 */
class ExpressionAttribute extends Attribute
{
    var $m_searchType = "string";
    var $m_expression;

    /**
     * Constructor.
     *
     * @param string $name The name of the attribute.
     * @param string $expression The SQL expression.
     * @param mixed $searchTypeOrFlags The search type (string) or flags (numeric) for this attribute. At the moment
     *                                   only search types "string", "number" and "date" are supported.
     * @param int $flags The flags for this attribute.
     */
    function __construct($name, $expression, $searchTypeOrFlags = 0, $flags = 0)
    {
        if (is_numeric($searchTypeOrFlags)) {
            $flags = $searchTypeOrFlags;
        }

        parent::__construct($name, $flags | self::AF_HIDE_ADD | self::AF_READONLY_EDIT);

        $this->m_expression = $expression;

        if (!is_numeric($searchTypeOrFlags)) {
            $this->setSearchType($searchTypeOrFlags);
        }
    }

    /**
     * No storage.
     *
     * @param string $mode The type of storage ("add" or "update")
     */
    function storageType($mode = '')
    {
        return self::NOSTORE;
    }

    /**
     * Adds this attribute to database queries.
     *
     * @param Query $query The SQL query object
     * @param string $tablename The name of the table of this attribute
     * @param string $fieldaliasprefix Prefix to use in front of the alias
     *                                 in the query.
     * @param array $rec The record that contains the value of this attribute.
     * @param int $level Recursion level if relations point to eachother, an
     *                   endless loop could occur if they keep loading
     *                   eachothers data. The $level is used to detect this
     *                   loop. If overriden in a derived class, any subcall to
     *                   an addToQuery method should pass the $level+1.
     * @param string $mode Indicates what kind of query is being processing:
     *                     This can be any action performed on a node (edit,
     *                     add, etc) Mind you that "add" and "update" are the
     *                     actions that store something in the database,
     *                     whereas the rest are probably select queries.
     */
    function addToQuery($query, $tablename = "", $fieldaliasprefix = "", $rec = "", $level = 0, $mode = "")
    {
        $expression = str_replace("[table]", $tablename, $this->m_expression);
        $query->addExpression($this->fieldName(), $expression, $fieldaliasprefix);
    }

    /**
     * Returns the order by statement for this attribute.
     *
     * @param array $extra A list of attribute names to add to the order by
     *                     statement
     * @param string $table The table name (if not given uses the owner node's table name)
     * @param string $direction Sorting direction (ASC or DESC)
     * @return string order by statement
     */
    function getOrderByStatement($extra = '', $table = '', $direction = 'ASC')
    {
        if (empty($table)) {
            $table = $this->m_ownerInstance->m_table;
        }

        $expression = str_replace("[table]", $table, $this->m_expression);

        $result = "($expression)";

        if ($this->getSearchType() == 'string') {
            $result = "LOWER({$result})";
        }

        $result .= ($direction ? " {$direction}" : "");

        return $result;
    }

    /**
     * Sets the search type.
     *
     * @param array $type the search type (string, number or date)
     */
    function setSearchType($type)
    {
        $this->m_searchType = $type;
    }

    /**
     * Returns the search type.
     *
     * @return string the search type (string, number or date)
     */
    function getSearchType()
    {
        return $this->m_searchType;
    }

    /**
     * We don't know our field type plus we can't be stored anyways.
     * So return an empty field type.
     *
     * @return string field type (empty string)
     */
    function dbFieldType()
    {
        return "";
    }

    /**
     * Returns the search modes.
     *
     * @return array list of search modes
     */
    function getSearchModes()
    {
        if ($this->getSearchType() == "number") {
            return NumberAttribute::getSearchModes();
        } else {
            if ($this->getSearchType() == "date") {
                return DateAttribute::getSearchModes();
            } else {
                return parent::getSearchModes();
            }
        }
    }

    /**
     * Returns a piece of html code that can be used to search for an attribute's value.
     *
     * @param array $record Array with values
     * @param boolean $extended if set to false, a simple search input is
     *                          returned for use in the searchbar of the
     *                          recordlist. If set to true, a more extended
     *                          search may be returned for the 'extended'
     *                          search page. The Attribute does not
     *                          make a difference for $extended is true, but
     *                          derived attributes may reimplement this.
     * @param string $fieldprefix The fieldprefix of this attribute's HTML element.
     *
     * @return String A piece of html-code
     */
    public function search($record, $extended = false, $fieldprefix = "", DataGrid $grid = null)
    {
        if ($this->getSearchType() == "number") {
            return NumberAttribute::search($record, $extended, $fieldprefix);
        } else {
            if ($this->getSearchType() == "date") {
                $attr = new DateAttribute($this->fieldName());
                $attr->m_searchsize = 10;
                return $attr->search($record, $extended, $fieldprefix);
            } else {
                return parent::search($record, $extended, $fieldprefix);
            }
        }
    }

    /**
     * Creates a search condition for this attribute.
     *
     * @param Query $query The query object where the search condition should be placed on
     * @param string $table The name of the table in which this attribute
     *                              is stored
     * @param mixed $value The value the user has entered in the searchbox
     * @param string $searchmode The searchmode to use. This can be any one
     *                              of the supported modes, as returned by this
     *                              attribute's getSearchModes() method.
     * @return String The searchcondition to use.
     */
    function getSearchCondition(Query $query, $table, $value, $searchmode, $fieldname = '')
    {
        // If we are accidentally mistaken for a relation and passed an array
        // we only take our own attribute value from the array
        if ($this->m_searchmode) {
            $searchmode = $this->m_searchmode;
        }

        $expression = "(" . str_replace("[table]", $table, $this->m_expression) . ")";

        if ($this->getSearchType() == "date") {
            $attr = new DateAttribute($this->fieldName());
            return $attr->getSearchCondition($query, $table, $value, $searchmode, $expression);
        }

        if ($this->getSearchType() == "number") {
            $value = NumberAttribute::processSearchValue($value, $searchmode);
        }

        if ($searchmode != "between") {
            if ($this->getSearchType() == "number") {
                if ($value['from'] != '') {
                    $value = $value['from'];
                } else {
                    if ($value['to'] != '') {
                        $value = $value['to'];
                    } else {
                        return false;
                    }
                }
            }
            $func = $searchmode . "Condition";
            if (method_exists($query, $func) && $value !== "" && $value !== null) {
                return $query->$func($expression, $this->escapeSQL($value));
            } else {
                return false;
            }
        } else {
            return NumberAttribute::getBetweenCondition($query, $expression, $value);
        }
    }
}