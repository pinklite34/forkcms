<?php

namespace Frontend\Core\Engine;

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * This is our extended version of SpoonForm.
 *
 * @author Davy Hellemans <davy.hellemans@netlash.com>
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 */
class Form extends \SpoonForm
{
    /**
     * The header instance
     *
     * @var    FrontendHeader
     */
    private $header;

    /**
     * The URL instance
     *
     * @var    FrontendURL
     */
    private $URL;

    /**
     * @param string $name     Name of the form.
     * @param string $action   The action (URL) whereto the form will be submitted, if not provided it will
     *                         be auto generated.
     * @param string $method   The method to use when submitting the form, default is POST.
     * @param string $hash     The id of the anchor to append to the action-URL.
     * @param bool   $useToken Should we automagically add a form token?
     */
    public function __construct($name, $action = null, $method = 'post', $hash = null, $useToken = true)
    {
        $this->URL = Model::getContainer()->get('url');
        $this->header = Model::getContainer()->get('header');

        $name = (string) $name;
        $hash = ($hash !== null) ? (string) $hash : null;
        $useToken = (bool) $useToken;
        $action = ($action === null) ? '/' . $this->URL->getQueryString() : (string) $action;

        // call the real form-class
        parent::__construct((string) $name, $action, $method, (bool) $useToken);

        // add default classes
        $this->setParameter('id', $name);
        $this->setParameter('class', 'forkForms submitWithLink');
    }

    /**
     * Adds a button to the form
     *
     * @param string $name  Name of the button.
     * @param string $value The value (or label) that will be printed.
     * @param        string [optional] $type The type of the button (submit is default).
     * @param        string [optional] $class Class(es) that will be applied on the button.
     * @return \SpoonFormButton
     */
    public function addButton($name, $value, $type = 'submit', $class = null)
    {
        $name = (string) $name;
        $value = (string) $value;
        $type = (string) $type;
        $class = ($class !== null) ? (string) $class : 'inputText inputButton';

        // do a check, only enable this if we use forms that are submitted with javascript
        if ($type == 'submit' && $name == 'submit') {
            throw new Exception(
                'You can\'t add buttons with the name submit. JS freaks out
                when we replace the buttons with a link and use that link to
                submit the form.'
            );
        }

        // create and return a button
        return parent::addButton($name, $value, $type, $class);
    }

    /**
     * Adds a single checkbox.
     *
     * @param string $name  The name of the element.
     * @param        bool   [optional] $checked Should the checkbox be checked?
     * @param        string [optional] $class Class(es) that will be applied on the element.
     * @param        string [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormCheckbox
     */
    public function addCheckbox($name, $checked = false, $class = null, $classError = null)
    {
        $name = (string) $name;
        $checked = (bool) $checked;
        $class = ($class !== null) ? (string) $class : 'inputCheckbox';
        $classError = ($classError !== null) ? (string) $classError : 'inputCheckboxError';

        // create and return a checkbox
        return parent::addCheckbox($name, $checked, $class, $classError);
    }

    /**
     * Adds a date field to the form
     *
     * @param string $name       Name of the element.
     * @param mixed  $value      The value for the element.
     * @param string $type       The type (from, till, range) of the datepicker.
     * @param int    $date       The date to use.
     * @param int    $date2      The second date for a rangepicker.
     * @param string $class      Class(es) that have to be applied on the element.
     * @param string $classError Class(es) that have to be applied when an error occurs on the element.
     * @return FrontendFormDate
     */
    public function addDate(
        $name,
        $value = null,
        $type = null,
        $date = null,
        $date2 = null,
        $class = null,
        $classError = null
    ) {
        $name = (string) $name;
        $value = ($value !== null) ? (($value !== '') ? (int) $value : '') : null;
        $type = \SpoonFilter::getValue($type, array('from', 'till', 'range'), 'none');
        $date = ($date !== null) ? (int) $date : null;
        $date2 = ($date2 !== null) ? (int) $date2 : null;
        $class = ($class !== null) ? (string) $class : 'inputText inputDate';
        $classError = ($classError !== null) ? (string) $classError : 'inputTextError inputDateError';

        // validate
        if ($type == 'from' && ($date == 0 || $date == null)) {
            throw new Exception('A date field with type "from" should have a valid date-parameter.');
        }
        if ($type == 'till' && ($date == 0 || $date == null)) {
            throw new Exception('A date field with type "till" should have a valid date-parameter.');
        }
        if ($type == 'range' && ($date == 0 || $date2 == 0 || $date == null || $date2 == null)) {
            throw new Exception('A date field with type "range" should have 2 valid date-parameters.');
        }

        // @later	get preferred mask & first day
        $mask = 'd/m/Y';
        $firstDay = 1;

        // build attributes
        $attributes['data-mask'] = str_replace(
            array('d', 'm', 'Y', 'j', 'n'),
            array('dd', 'mm', 'yy', 'd', 'm'),
            $mask
        );
        $attributes['data-firstday'] = $firstDay;

        // add extra classes based on type
        switch ($type) {
            // start date
            case 'from':
                $class .= ' inputDatefieldFrom inputText';
                $classError .= ' inputDatefieldFrom';
                $attributes['data-startdate'] = date('Y-m-d', $date);
                break;

            // end date
            case 'till':
                $class .= ' inputDatefieldTill inputText';
                $classError .= ' inputDatefieldTill';
                $attributes['data-enddate'] = date('Y-m-d', $date);
                break;

            // date range
            case 'range':
                $class .= ' inputDatefieldRange inputText';
                $classError .= ' inputDatefieldRange';
                $attributes['data-startdate'] = date('Y-m-d', $date);
                $attributes['data-enddate'] = date('Y-m-d', $date2);
                break;

            // normal date field
            default:
                $class .= ' inputDatefieldNormal inputText';
                $classError .= ' inputDatefieldNormal';
                break;
        }

        // create a datefield
        $this->add(new FrontendFormDate($name, $value, $mask, $class, $classError));

        // set attributes
        parent::getField($name)->setAttributes($attributes);

        // return date field
        return parent::getField($name);
    }

    /**
     * Adds a single dropdown.
     *
     * @param string $name  Name of the element.
     * @param        array  [optional] $values Values for the dropdown.
     * @param        string [optional] $selected The selected elements.
     * @param        bool   [optional] $multipleSelection Is it possible to select multiple items?
     * @param        string [optional] $class Class(es) that will be applied on the element.
     * @param        string [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormDropdown
     */
    public function addDropdown(
        $name,
        array $values = null,
        $selected = null,
        $multipleSelection = false,
        $class = null,
        $classError = null
    ) {
        $name = (string) $name;
        $values = (array) $values;
        $selected = ($selected !== null) ? $selected : null;
        $multipleSelection = (bool) $multipleSelection;
        $class = ($class !== null) ? (string) $class : 'select';
        $classError = ($classError !== null) ? (string) $classError : 'selectError';

        // special classes for multiple
        if ($multipleSelection) {
            $class .= ' selectMultiple';
            $classError .= ' selectMultipleError';
        }

        // create and return a dropdown
        return parent::addDropdown($name, $values, $selected, $multipleSelection, $class, $classError);
    }

    /**
     * Adds a single file field.
     *
     * @param string $name  Name of the element.
     * @param        string [optional] $class Class(es) that will be applied on the element.
     * @param        string [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormFile
     */
    public function addFile($name, $class = null, $classError = null)
    {
        $name = (string) $name;
        $class = ($class !== null) ? (string) $class : 'inputFile';
        $classError = ($classError !== null) ? (string) $classError : 'inputFileError';

        // create and return a file field
        return parent::addFile($name, $class, $classError);
    }

    /**
     * Adds a single image field.
     *
     * @param string $name  The name of the element.
     * @param        string [optional] $class Class(es) that will be applied on the element.
     * @param        string [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormImage
     */
    public function addImage($name, $class = null, $classError = null)
    {
        $name = (string) $name;
        $class = ($class !== null) ? (string) $class : 'inputFile inputImage';
        $classError = ($classError !== null) ? (string) $classError : 'inputFileError inputImageError';

        // add element
        $this->add(new FrontendFormImage($name, $class, $classError));

        return $this->getField($name);
    }

    /**
     * Adds a multiple checkbox.
     *
     * @param string $name   The name of the element.
     * @param array  $values The values for the checkboxes.
     * @param        mixed   [optional] $checked Should the checkboxes be checked?
     * @param        string  [optional] $class Class(es) that will be applied on the element.
     * @param        string  [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormMultiCheckbox
     */
    public function addMultiCheckbox($name, array $values, $checked = null, $class = null, $classError = null)
    {
        $name = (string) $name;
        $values = (array) $values;
        $checked = ($checked !== null) ? (array) $checked : null;
        $class = ($class !== null) ? (string) $class : 'inputCheckbox';
        $classError = ($classError !== null) ? (string) $classError : 'inputCheckboxError';

        // create and return a multi checkbox
        return parent::addMultiCheckbox($name, $values, $checked, $class, $classError);
    }

    /**
     * Adds a single password field.
     *
     * @param string $name  The name of the field.
     * @param        string [optional] $value The value for the field.
     * @param        int    [optional] $maxLength The maximum length for the field.
     * @param        string [optional] $class Class(es) that will be applied on the element.
     * @param        string [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @param        bool   [optional] $HTML Will the field contain HTML?
     * @return \SpoonFormPassword
     */
    public function addPassword(
        $name,
        $value = null,
        $maxLength = null,
        $class = null,
        $classError = null,
        $HTML = false
    ) {
        $name = (string) $name;
        $value = ($value !== null) ? (string) $value : null;
        $maxLength = ($maxLength !== null) ? (int) $maxLength : null;
        $class = ($class !== null) ? (string) $class : 'inputText inputPassword';
        $classError = ($classError !== null) ? (string) $classError : 'inputTextError inputPasswordError';
        $HTML = (bool) $HTML;

        // create and return a password field
        return parent::addPassword($name, $value, $maxLength, $class, $classError, $HTML);
    }

    /**
     * Adds a single radio button.
     *
     * @param string $name   The name of the element.
     * @param array  $values The possible values for the radio button.
     * @param        string  [optional] $checked Should the element be checked?
     * @param        string  [optional] $class Class(es) that will be applied on the element.
     * @param        string  [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormRadiobutton
     */
    public function addRadiobutton($name, array $values, $checked = null, $class = null, $classError = null)
    {
        $name = (string) $name;
        $values = (array) $values;
        $checked = ($checked !== null) ? (string) $checked : null;
        $class = ($class !== null) ? (string) $class : 'inputRadio';
        $classError = ($classError !== null) ? (string) $classError : 'inputRadioError';

        // create and return a radio button
        return parent::addRadiobutton($name, $values, $checked, $class, $classError);
    }

    /**
     * Adds a single textfield.
     *
     * @param string $name  The name of the element.
     * @param        string [optional] $value The value inside the element.
     * @param        int    [optional] $maxLength The maximum length for the value.
     * @param        string [optional] $class Class(es) that will be applied on the element.
     * @param        string [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @param        bool   [optional] $HTML Will this element contain HTML?
     * @return \SpoonFormText
     */
    public function addText($name, $value = null, $maxLength = 255, $class = null, $classError = null, $HTML = false)
    {
        $name = (string) $name;
        $value = ($value !== null) ? (string) $value : null;
        $maxLength = ($maxLength !== null) ? (int) $maxLength : null;
        $class = ($class !== null) ? (string) $class : 'inputText';
        $classError = ($classError !== null) ? (string) $classError : 'inputTextError';
        $HTML = (bool) $HTML;

        // create and return a textfield
        return parent::addText($name, $value, $maxLength, $class, $classError, $HTML);
    }

    /**
     * Adds a single textarea.
     *
     * @param string $name  The name of the element.
     * @param        string [optional] $value The value inside the element.
     * @param        string [optional] $class Class(es) that will be applied on the element.
     * @param        string [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @param        bool   [optional] $HTML Will the element contain HTML?
     * @return \SpoonFormTextarea
     */
    public function addTextarea($name, $value = null, $class = null, $classError = null, $HTML = false)
    {
        $name = (string) $name;
        $value = ($value !== null) ? (string) $value : null;
        $class = ($class !== null) ? (string) $class : 'textarea';
        $classError = ($classError !== null) ? (string) $classError : 'textareaError';
        $HTML = (bool) $HTML;

        // create and return a textarea
        return parent::addTextarea($name, $value, $class, $classError, $HTML);
    }

    /**
     * Adds a single time field.
     *
     * @param string $name  The name of the element.
     * @param        string [optional] $value The value inside the element.
     * @param        string [optional] $class Class(es) that will be applied on the element.
     * @param        string [optional] $classError Class(es) that will be applied on the element when an error occurs.
     * @return \SpoonFormTime
     */
    public function addTime($name, $value = null, $class = null, $classError = null)
    {
        $name = (string) $name;
        $value = ($value !== null) ? (string) $value : null;
        $class = ($class !== null) ? (string) $class : 'inputText inputTime';
        $classError = ($classError !== null) ? (string) $classError : 'inputTextError inputTimeError';

        // create and return a time field
        return parent::addTime($name, $value, $class, $classError);
    }

    /**
     * Generates an example template, based on the elements already added.
     *
     * @return string
     */
    public function getTemplateExample()
    {
        // start form
        $value = "\n";
        $value .= '{form:' . $this->getName() . "}\n";

        /**
         * At first all the hidden fields need to be added to this form, since
         * they're not shown and are best to be put right beneath the start of the form tag.
         */
        foreach ($this->getFields() as $object) {
            // is a hidden field
            if (($object instanceof \SpoonFormHidden) && $object->getName() != 'form') {
                $value .= "\t" . '{$hid' . str_replace('[]', '', \SpoonFilter::toCamelCase($object->getName())) . "}\n";
            }
        }

        /**
         * Add all the objects that are NOT hidden fields. Based on the existance of some methods
         * errors will or will not be shown.
         */
        foreach ($this->getFields() as $object) {
            // NOT a hidden field
            if (!($object instanceof \SpoonFormHidden)) {
                if ($object instanceof \SpoonFormButton) {
                    $value .= "\t" . '<p>' . "\n";
                    $value .= "\t\t" . '{$btn' . \SpoonFilter::toCamelCase($object->getName()) . '}' . "\n";
                    $value .= "\t" . '</p>' . "\n\n";
                } elseif ($object instanceof \SpoonFormCheckbox) {
                    $value .= "\t" . '<p{option:chk' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error} class="errorArea"{/option:chk' .
                              \SpoonFilter::toCamelCase($object->getName()) . 'Error}>' . "\n";
                    $value .= "\t\t" . '<label for="' . $object->getAttribute('id') . '">' .
                              \SpoonFilter::toCamelCase($object->getName()) . '</label>' . "\n";
                    $value .= "\t\t" . '{$chk' . \SpoonFilter::toCamelCase($object->getName()) .
                              '} {$chk' . \SpoonFilter::toCamelCase($object->getName()) . 'Error}' . "\n";
                    $value .= "\t" . '</p>' . "\n\n";
                } elseif ($object instanceof \SpoonFormMultiCheckbox) {
                    $value .= "\t" . '<div{option:chk' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error} class="errorArea"{/option:chk' .
                              \SpoonFilter::toCamelCase($object->getName()) . 'Error}>' . "\n";
                    $value .= "\t\t" . '<p class="label">' . \SpoonFilter::toCamelCase($object->getName()) .
                              '</p>' . "\n";
                    $value .= "\t\t" . '{$chk' . \SpoonFilter::toCamelCase($object->getName()) . 'Error}' . "\n";
                    $value .= "\t\t" . '<ul class="inputList">' . "\n";
                    $value .= "\t\t\t" . '{iteration:' . $object->getName() . '}' . "\n";
                    $value .= "\t\t\t\t" . '<li><label for="{$' . $object->getName() . '.id}">{$' .
                              $object->getName() . '.chk' . \SpoonFilter::toCamelCase($object->getName()) .
                              '} {$' . $object->getName() . '.label}</label></li>' . "\n";
                    $value .= "\t\t\t" . '{/iteration:' . $object->getName() . '}' . "\n";
                    $value .= "\t\t" . '</ul>' . "\n";
                    $value .= "\t" . '</div>' . "\n\n";
                } elseif ($object instanceof \SpoonFormDropdown) {
                    $value .= "\t" . '<p{option:ddm' .
                              str_replace(
                                  '[]',
                                  '',
                                  \SpoonFilter::toCamelCase($object->getName())
                              ) . 'Error} class="errorArea"{/option:ddm' .
                              str_replace(
                                  '[]',
                                  '',
                                  \SpoonFilter::toCamelCase($object->getName())
                              ) . 'Error}>' . "\n";
                    $value .= "\t\t" . '<label for="' . $object->getAttribute('id') . '">' .
                              str_replace(
                                  '[]',
                                  '',
                                  \SpoonFilter::toCamelCase($object->getName())
                              ) . '</label>' . "\n";
                    $value .= "\t\t" . '{$ddm' . str_replace('[]', '', \SpoonFilter::toCamelCase($object->getName())) .
                              '} {$ddm' .
                              str_replace(
                                  '[]',
                                  '',
                                  \SpoonFilter::toCamelCase($object->getName())
                              ) . 'Error}' . "\n";
                    $value .= "\t" . '</p>' . "\n\n";
                } elseif ($object instanceof \SpoonFormImage) {
                    $value .= "\t" . '<p{option:file' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error} class="errorArea"{/option:file' .
                              \SpoonFilter::toCamelCase($object->getName()) . 'Error}>' . "\n";
                    $value .= "\t\t" . '<label for="' . $object->getAttribute('id') . '">' .
                              \SpoonFilter::toCamelCase($object->getName()) . '</label>' . "\n";
                    $value .= "\t\t" . '{$file' . \SpoonFilter::toCamelCase($object->getName()) .
                              '} <span class="helpTxt">{$msgHelpImageField}</span> {$file' .
                              \SpoonFilter::toCamelCase($object->getName()) . 'Error}' . "\n";
                    $value .= "\t" . '</p>' . "\n\n";
                } elseif ($object instanceof \SpoonFormFile) {
                    $value .= "\t" . '<p{option:file' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error} class="errorArea"{/option:file' .
                              \SpoonFilter::toCamelCase($object->getName()) . 'Error}>' . "\n";
                    $value .= "\t\t" . '<label for="' . $object->getAttribute('id') . '">' .
                              \SpoonFilter::toCamelCase($object->getName()) . '</label>' . "\n";
                    $value .= "\t\t" . '{$file' . \SpoonFilter::toCamelCase($object->getName()) .
                              '} {$file' . \SpoonFilter::toCamelCase($object->getName()) . 'Error}' . "\n";
                    $value .= "\t" . '</p>' . "\n\n";
                } elseif ($object instanceof \SpoonFormRadiobutton) {
                    $value .= "\t" . '<div{option:rbt' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error} class="errorArea"{/option:rbt' .
                              \SpoonFilter::toCamelCase($object->getName()) . 'Error}>' . "\n";
                    $value .= "\t\t" . '<p class="label">' . \SpoonFilter::toCamelCase($object->getName()) .
                              '</p>' . "\n";
                    $value .= "\t\t" . '{$rbt' . \SpoonFilter::toCamelCase($object->getName()) . 'Error}' . "\n";
                    $value .= "\t\t" . '<ul class="inputList">' . "\n";
                    $value .= "\t\t\t" . '{iteration:' . $object->getName() . '}' . "\n";
                    $value .= "\t\t\t\t" . '<li><label for="{$' . $object->getName() . '.id}">{$' .
                              $object->getName() . '.rbt' . \SpoonFilter::toCamelCase($object->getName()) .
                              '} {$' . $object->getName() . '.label}</label></li>' . "\n";
                    $value .= "\t\t\t" . '{/iteration:' . $object->getName() . '}' . "\n";
                    $value .= "\t\t" . '</ul>' . "\n";
                    $value .= "\t" . '</div>' . "\n\n";
                } elseif ($object instanceof \SpoonFormDate) {
                    $value .= "\t" . '<p{option:txt' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error} class="errorArea"{/option:txt' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error}>' . "\n";
                    $value .= "\t\t" . '<label for="' . $object->getAttribute('id') . '">' .
                              \SpoonFilter::toCamelCase($object->getName()) . '</label>' . "\n";
                    $value .= "\t\t" . '{$txt' . \SpoonFilter::toCamelCase($object->getName()) .
                              '} <span class="helpTxt">{$msgHelpDateField}</span> {$txt' .
                              \SpoonFilter::toCamelCase($object->getName()) . 'Error}' . "\n";
                    $value .= "\t" . '</p>' . "\n\n";
                } elseif ($object instanceof \SpoonFormTime) {
                    $value .= "\t" . '<p{option:txt' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error} class="errorArea"{/option:txt' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error}>' . "\n";
                    $value .= "\t\t" . '<label for="' . $object->getAttribute('id') . '">' .
                              \SpoonFilter::toCamelCase($object->getName()) . '</label>' . "\n";
                    $value .= "\t\t" . '{$txt' . \SpoonFilter::toCamelCase($object->getName()) .
                              '} <span class="helpTxt">{$msgHelpTimeField}</span> {$txt' .
                              \SpoonFilter::toCamelCase($object->getName()) . 'Error}' . "\n";
                    $value .= "\t" . '</p>' . "\n\n";
                } elseif (($object instanceof \SpoonFormPassword) ||
                          ($object instanceof \SpoonFormTextarea) ||
                          ($object instanceof \SpoonFormText)
                ) {
                    $value .= "\t" . '<p{option:txt' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error} class="errorArea"{/option:txt' . \SpoonFilter::toCamelCase($object->getName()) .
                              'Error}>' . "\n";
                    $value .= "\t\t" . '<label for="' . $object->getAttribute('id') . '">' .
                              \SpoonFilter::toCamelCase($object->getName()) . '</label>' . "\n";
                    $value .= "\t\t" . '{$txt' . \SpoonFilter::toCamelCase($object->getName()) .
                              '} {$txt' . \SpoonFilter::toCamelCase($object->getName()) . 'Error}' . "\n";
                    $value .= "\t" . '</p>' . "\n\n";
                }
            }
        }

        return $value . '{/form:' . $this->getName() . '}';
    }

    /**
     * Fetches all the values for this form as key/value pairs
     *
     * @param mixed [optional] $excluded Which elements should be excluded?
     * @return array
     */
    public function getValues($excluded = array('form', 'save', '_utf8'))
    {
        return parent::getValues($excluded);
    }

    /**
     * Parse the form
     *
     * @param \SpoonTemplate $tpl The template instance wherein the form will be parsed.
     */
    public function parse(\SpoonTemplate $tpl)
    {
        // parse the form
        parent::parse($tpl);

        // validate the form
        $this->validate();

        // if the form is submitted but there was an error, assign a general error
        if ($this->isSubmitted() && !$this->isCorrect()) {
            $tpl->assign('formError', true);
        }
    }
}

/**
 * This is our extended version of \SpoonFormDate
 *
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 */
class FrontendFormDate extends \SpoonFormDate
{
    /**
     * Checks if this field is correctly submitted.
     *
     * @param string [optional] $error The error message to set.
     * @return bool
     */
    public function isValid($error = null)
    {
        // call parent (let them do the hard word)
        $return = parent::isValid($error);

        // already errors detect, no more further testing is needed
        if ($return === false) {
            return false;
        }

        // define long mask
        $longMask = str_replace(array('d', 'm', 'y', 'Y'), array('dd', 'mm', 'yy', 'yyyy'), $this->mask);

        // post/get data
        $data = $this->getMethod(true);

        // init some vars
        $year = (strpos($longMask, 'yyyy') !== false) ? substr(
            $data[$this->attributes['name']],
            strpos($longMask, 'yyyy'),
            4
        ) : substr($data[$this->attributes['name']], strpos($longMask, 'yy'), 2);
        $month = substr($data[$this->attributes['name']], strpos($longMask, 'mm'), 2);
        $day = substr($data[$this->attributes['name']], strpos($longMask, 'dd'), 2);

        // validate datefields that have a from-date set
        if (strpos($this->attributes['class'], 'inputDatefieldFrom') !== false) {
            // process from date
            $fromDateChunks = explode('-', $this->attributes['data-startdate']);
            $fromDateTimestamp = mktime(12, 00, 00, $fromDateChunks[1], $fromDateChunks[2], $fromDateChunks[0]);

            // process given date
            $givenDateTimestamp = mktime(12, 00, 00, $month, $day, $year);

            // compare dates
            if ($givenDateTimestamp < $fromDateTimestamp) {
                if ($error !== null) {
                    $this->setError($error);
                }

                return false;
            }
        } elseif (strpos($this->attributes['class'], 'inputDatefieldTill') !== false) {
            // process till date
            $tillDateChunks = explode('-', $this->attributes['data-enddate']);
            $tillDateTimestamp = mktime(12, 00, 00, $tillDateChunks[1], $tillDateChunks[2], $tillDateChunks[0]);

            // process given date
            $givenDateTimestamp = mktime(12, 00, 00, $month, $day, $year);

            // compare dates
            if ($givenDateTimestamp > $tillDateTimestamp) {
                if ($error !== null) {
                    $this->setError($error);
                }

                return false;
            }
        } elseif (strpos($this->attributes['class'], 'inputDatefieldRange') !== false) {
            // process from date
            $fromDateChunks = explode('-', $this->attributes['data-startdate']);
            $fromDateTimestamp = mktime(12, 00, 00, $fromDateChunks[1], $fromDateChunks[2], $fromDateChunks[0]);

            // process till date
            $tillDateChunks = explode('-', $this->attributes['data-enddate']);
            $tillDateTimestamp = mktime(12, 00, 00, $tillDateChunks[1], $tillDateChunks[2], $tillDateChunks[0]);

            // process given date
            $givenDateTimestamp = mktime(12, 00, 00, $month, $day, $year);

            // compare dates
            if ($givenDateTimestamp < $fromDateTimestamp || $givenDateTimestamp > $tillDateTimestamp) {
                if ($error !== null) {
                    $this->setError($error);
                }

                return false;
            }
        }

        /**
         * When the code reaches the point, it means no errors have occurred
         * and truth will out!
         */

        return true;
    }
}

/**
 * This is our extended version of \SpoonFormImage
 *
 * @author Tijs Verkoyen <tijs@sumocoders.be>
 */
class FrontendFormImage extends \SpoonFormImage
{
    /**
     * Generate thumbnails based on the folders in the path
     * Use
     *  - 128x128 as folder name to generate an image that where the width will be 128px and the height will be 128px
     *  - 128x as folder name to generate an image that where the width will be 128px,
     *      the height will be calculated based on the aspect ratio.
     *  - x128 as folder name to generate an image that where the width will be 128px,
     *      the height will be calculated based on the aspect ratio.
     *
     * @param string $path
     * @param string $filename
     */
    public function generateThumbnails($path, $filename)
    {
        // create folder if needed
        $fs = new Filesystem();
        if (!$fs->exists($path . '/source')) {
            $fs->mkdir($path . '/source');
        }

        // move the source file
        $this->moveFile($path . '/source/' . $filename);

        // generate the thumbnails
        Model::generateThumbnails($path, $path . '/source/' . $filename);
    }

    /**
     * This function will return the errors. It is extended so we can do image checks automatically.
     *
     * @return string
     */
    public function getErrors()
    {
        // do an image validation
        if ($this->isFilled()) {
            $this->isAllowedExtension(array('jpg', 'jpeg', 'gif', 'png'), FL::err('JPGGIFAndPNGOnly'));
            $this->isAllowedMimeType(array('image/jpeg', 'image/gif', 'image/png'), FL::err('JPGGIFAndPNGOnly'));
        }

        return $this->errors;
    }
}
