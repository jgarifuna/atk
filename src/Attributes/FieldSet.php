<?php namespace Sintattica\Atk\Attributes;

use Sintattica\Atk\Utils\StringParser;

/**
 * A fieldset can be used to combine multiple attributes to a single
 * attribute in edit/view mode.
 *
 * @author Peter C. Verhage <peter@ibuildings.nl>
 * @package atk
 * @subpackage attributes
 */
class FieldSet extends Attribute
{
    private $m_template;
    private $m_parser;

    /**
     * Constructor.
     *
     * @param string $name fieldset name
     * @param string $template template string
     * @param int $flags flags
     */
    public function __construct($name, $template, $flags = 0)
    {
        parent::__construct($name, $flags | self::AF_NO_SORT | self::AF_HIDE_SEARCH);
        $this->setTemplate($template);
        $this->setLoadType(self::NOLOAD);
        $this->setStorageType(self::NOSTORE);
    }

    /**
     * Is empty?
     *
     * @return boolean
     */
    public function isEmpty()
    {
        // always return false, this way you can mark a field-set as obligatory
        // as a visual cue without ATK complaining that no value has been set
        return false;
    }

    /**
     * Check if one of the fields contains an error.
     *
     * @param array $errors The error list is one that is stored in the
     *                      "atkerror" section of a record, for example
     *                      generated by validate() methods.
     * @return boolean
     */
    function getError($errors)
    {
        $fields = array_unique($this->getParser()->getFields());

        foreach ($fields as $field) {
            @list($attrName) = explode('.', $field);
            $attr = $this->getOwnerInstance()->getAttribute($attrName);
            if ($attr->getError($errors)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the fieldset template.
     *
     * @return string template string
     */
    public function getTemplate()
    {
        return $this->m_template;
    }

    /**
     * Sets the fieldset template. To include an attribute use [attribute name].
     *
     * @param string $template template string
     */
    public function setTemplate($template)
    {
        $this->m_template = $template;
        $this->m_parser = null;
    }

    /**
     * Returns the string parser instance for the fieldset template.
     *
     * @return StringParser
     */
    protected function getParser()
    {
        if ($this->m_parser == null) {
            $this->m_parser = new StringParser($this->getTemplate());
        }

        return $this->m_parser;
    }

    /**
     * Make sure we disable the normal rendering for attributes that
     * are part of this fieldset.
     */
    public function postInit()
    {
        $fields = $this->getParser()->getFields();
        foreach ($fields as $field) {
            list($attrName) = explode('.', $field);
            $attr = $this->getOwnerInstance()->getAttribute($attrName);
            $attr->addDisabledMode(self::DISABLED_VIEW | self::DISABLED_EDIT);
            $attr->setTabs($this->getTabs());
            $attr->setSections($this->getSections());
        }
    }

    /**
     * Renders the fieldset.
     *
     * @param string $type edit or display
     * @param array $record record
     * @param string $mode mode
     * @param string $fieldprefix fieldprefix
     *
     * @return string rendered HTML
     */
    protected function renderFieldSet($type, $record, $mode, $fieldprefix = '')
    {
        $replacements = array();

        $fields = array_unique($this->getParser()->getFields());

        foreach ($fields as $attrName) {
            $attr = $this->getOwnerInstance()->getAttribute($attrName);

            // render the field
            if ($type == 'edit') {
                if (($mode == 'edit' && $attr->hasFlag(self::AF_HIDE_EDIT)) || ($mode == 'add' && $attr->hasFlag(self::AF_HIDE_ADD))) {
                    $field = '';
                } else {
                    $field = $attr->getEdit($mode, $record, $fieldprefix);
                }
            } else {
                if ($type == 'display') {
                    if (($mode == 'view' && $attr->hasFlag(self::AF_HIDE_VIEW))) {
                        $field = '';
                    } else {
                        $field = $attr->getView($mode, $record);
                    }
                }
            }

            if ($field) {
                // render the label
                if (!$attr->hasFlag(self::AF_NO_LABEL)) {
                    $label = $attr->getLabel($record, $mode) . ': ';
                } else {
                    $label = '';
                }

                // wrap in a div with appropriate id in order to properly handle a refreshAttribute (v. atkEditFormModifier)
                $html = sprintf('%s<div id="%s_%s_%s">%s</div>',
                    $label, $this->getOwnerInstance()->getModule(), $this->getOwnerInstance()->getType(), $attrName,
                    $field
                );

                $replacements[$attrName] = $html;

            } else {
                $replacements[$attrName] = '';
            }
        }

        return '<div class="atkfieldset">' . $this->getParser()->parse($replacements) . '</div>';
    }

    /**
     * Edit fieldset.
     *
     * @param array $record
     * @param string $fieldprefix
     * @param string $mode
     *
     * @return string
     */
    function edit($record, $fieldprefix = '', $mode = '')
    {
        return $this->renderFieldSet('edit', $record, $mode, $fieldprefix);
    }

    /**
     * Display fieldset.
     *
     * @param string $record
     * @param string $mode
     *
     * @return string
     */
    function display($record, $mode = '')
    {
        return $this->renderFieldSet('display', $record, $mode);
    }
}
