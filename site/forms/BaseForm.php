<?php
/**
 * Base form class for rendering styled form elements
 */

namespace Thrust\Forms;

use Phalcon\Forms\Form;

use Phalcon\Forms\Element\Text;
use Phalcon\Forms\Element\Numeric;
use Phalcon\Forms\Element\Hidden;
use Phalcon\Forms\Element\Password;
use Phalcon\Forms\Element\Submit;
use Phalcon\Forms\Element\Check;
use Phalcon\Forms\Element\Select;

use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Email;
use Phalcon\Validation\Validator\Identical;
use Phalcon\Validation\Validator\StringLength;
use Phalcon\Validation\Validator\Regex;
use Phalcon\Validation\Validator\CreditCard;
use Phalcon\Validation\Message;
use Phalcon\Validation\Message\Group as MessageGroup;

class BaseForm extends Form {
    protected $fields;
    protected $validation;
    protected $saved_data;
    protected $errors;

    /**
     * constructor
     */
    function __construct()
    {
        parent::__construct();

        $this->fields = [];
        $this->saved_data = [];
        $this->errors = [];
        $this->validation = new Validation();
    }

    /**
     * Returns the default value for field 'csrf'
     */
    public function renderCsrf()
    {
        return $this->renderItem($this->get('csrf'));
    }

    /**
     * Render form field
     * @param $name
     * @param array $attr
     * @return string
     */
    public function renderDecorated($name, $attr = [], $gridClass = '')
    {
        $e = $this->get($name);

        // foundation abide
        $abide = '';
        if (isset($attr['abide'])) {
            $abide = '<small class="form-error" data-msg="' . $attr['abide']['error'] . '"></small>';
            $attr['pattern'] = $attr['abide']['pattern'];
            unset($attr['abide']);
        } else if (isset($attr['required'])) {
            $abide = '<small class="form-error">This field is required</small>';
        }

        // tooltip
        $tooltip = '';
        if (isset($attr['tooltip_text'])) {
            $tooltip = '<span class="signup_tooltip hide-for-small-only" data-tooltip data-allow-html="true" title="' . $attr['tooltip_text'] . '">?</span>';
            unset($attr['tooltip_text']);
        }

        $html = '';
        if (get_class($e) != 'Phalcon\Forms\Element\Hidden') {
            $html .= '<div class="form-group columns ' . $gridClass . '">';
        }
            $html .= $e->render($attr);
            // $html .= '<p><pre>' . print_r($attr, true) . '</pre></p>';

            if (get_class($e) == 'Phalcon\Forms\Element\Password') {
                $html .= '<a href="javascript:void(0);" class="toggle_pwd">Show</a>';
            }

            if (in_array(get_class($e), [
                'Phalcon\Forms\Element\Text',
                'Phalcon\Forms\Element\Numeric',
                'Phalcon\Forms\Element\Password',
            ])) {
                $html .= '<label class="control-label" for="' . $e->getName() . '">' . $e->getLabel() . '</label><i class="bar"></i>';
                $html .= $abide;
                $html .= $tooltip;
            }
        if (get_class($e) != 'Phalcon\Forms\Element\Hidden') {
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Render form field with errors displayed
     * @param $name
     * @param array $attr
     * @return string
     */
    public function renderDecoratedErrors($name, $errors, $attr = [], $gridClass)
    {
        $e = $this->get($name);

        // foundation abide
        $abide = '';
        if (isset($attr['abide'])) {
            $abide = '<small class="form-error">' . $attr['abide']['error'] . '</small>';
            $attr['pattern'] = $attr['abide']['pattern'];
            unset($attr['abide']);
        }

        // tooltip
        $tooltip = '';
        if (isset($attr['tooltip_text'])) {
            $tooltip = '<span class="signup_tooltip hide-for-small-only" data-tooltip data-allow-html="true" title="' . $attr['tooltip_text'] . '">?</span>';
            unset($attr['tooltip_text']);
        }

        $html = '<div class="group error columns ' . $gridClass . '">';
        $html .= $e->render($attr);
        $html .= '<span class="highlight"></span>';
        $html .= '<span class="bar"></span>';
        $html .= '<label class="reg" for="' . $e->getName() . '">' . $e->getLabel() . '</label>';
        $html .= '<div class="error_text">' . implode('<br />', $errors) . '</div>';
        $html .= $abide;
        $html .= $tooltip;
        $html .= '</div>';

        return $html;
    }

    /**
     * Render classic form field
     * @param $e
     * @param array $attr
     * @return string
     */
    public function renderItem($e, $attr = [])
    {
        return '<label for="' . $e->getName() . '">' . $e->getLabel() . '</label>' . $e->render($attr);
    }

    /**
     * Render errors
     * @param $e
     * @return string
     */
    public function renderFieldErrors($e)
    {
        $m = $this->getMessagesFor($e->getName());

        if (count($m)) {
            $r = '<ul class="err_msg">';
            foreach ($m as $i) {
                $r .= '<li>' . $this->flash->error($i) . '</li>';
            }
            return $r . '</ul>';
        }
    }

    /**
     * Check if specific field by $e has assigned errors
     * @param $e
     * @return bool
     */
    public function hasErrors($e)
    {
        $m = $this->getMessagesFor($e->getName());
        if (count($m))
            return true;
        return false;
    }

    /**
     * Decorator for form errors
     * @return null|string
     */
    public function renderErrorsDecorated()
    {
        if (count($this->getMessages())) {
            $r = '<div class="alert alert-danger"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
            foreach ($this->getMessages() as $key => $messages) {
                $r .= '<p>' . implode('</p><p>', $messages) . '</p><br />';
            }
            return $r . '</div>';
        }
        return null;
    }

    /**
     * Appends custom message into form.
     *
     * @param   mixed   $message
     * @param   string  $field
     * @param   string  $type
     * @return  void
     * @throws  \Phalcon\Forms\Exception
     */
    public function appendMessage($message, $field, $type = null)
    {
        if (is_string($message)) {
            $message = new Message($message, $field, $type);
        }

        if ($message instanceof Message || $message instanceof ModelMessage) {
            // Check if there is a group for the field already.
            if (! is_null($this->_messages) && array_key_exists($field, $this->_messages)) {
                $this->_messages[$field]->appendMessage($message);
            }
            else {
                $this->_messages[$field] = new MessageGroup(array($message));
            }
        } else {
            throw new Exception("Can't append message into the form, invalid type.");
        }
    }

    /**
     * Prints messages for a specific element.
     */
    public function messages($name)
    {
        if ($this->hasMessagesFor($name)) {
            foreach ($this->getMessagesFor($name) as $message) {
                $this->flash->error($message);
            }
        }
    }

    /**
     * Rewrite validation class to handle afterValidateEvent
     * @param null $data
     * @param null $entity
     * @return bool
     */
    public function isValid($data = null, $entity = null)
    {
//        return (parent::isValid($data, $entity) && $this->afterValidation($data));
        return parent::isValid($data, $entity);
    }

    /**
     * afterValidation dummy class. Will return true
     * @param $data
     * @return bool
     */
    public function afterValidation($data)
    {
        return true;
    }

    //--- helper-like features of base form

    public function getFields()
    {
        return $this->fields;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function getSavedData()
    {
        return $this->saved_data;
    }

    public function setSavedData($saved_data)
    {
        $this->saved_data = $saved_data;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function setErrors($errors)
    {
        $this->errors = $errors;
    }

    /**
     * get validation for current form
     * @return mixed
     */
    public function getValidation()
    {
        return $this->validation;
    }

    public function buildFields()
    {
        foreach ($this->fields as $field_id => $field) {
            if (in_array($field['type'], ['Label', 'Spacer', 'Separator'])) {
                // the caption field - don't add it to the form
                continue;
            }

            $type = "Phalcon\\Forms\\Element\\" . $field['type'];
            if (!class_exists($type)) {
                $type = "Thrust\\Forms\\Element\\" . $field['type'];
            }
            if (isset($field['options'])) {
                $element = new $type($field_id, $field['options']);
            } else {
                $element = new $type($field_id);
            }

            if (isset($field['label'])) {
                $element->setLabel($field['label']);
            }

            if (isset($field['validators'])) {
                foreach ($field['validators'] as $validator_type => $validator) {
                    $validator_type_full = "Phalcon\\Validation\\Validator\\" . $validator_type;
                    $this->validation->add($field_id, new $validator_type_full($validator));
                }
            }

            $this->add($element);
        }

        // CSRF - this will be added to every form
        $csrf = new Hidden('csrf');

        $csrf->addValidator(new Identical(array(
            'value'   => $this->security->getSessionToken(),
            'message' => 'CSRF validation failed',
        )));

        $this->add($csrf);

        return true;
    }

    /**
     * render form field
     * @param $field_id
     * @return string
     */
    public function renderField($field_id)
    {
        if (! isset($this->fields[$field_id])) return false;

        $field = $this->fields[$field_id];

        $html = '';
        if ($field['type'] == 'Label') {
            // label field
            $html .= $this->_addLabel($field);
        } else if ($field['type'] == 'Spacer') {
            $html .= '<div style="clear: both; height: ' . $field['height'] . '"></div>';
        } else if ($field['type'] == 'Separator') {
            $html .= '<div class="columns" style="float: none"><div style="clear: both; border-bottom: 1px solid ' . $field['color'] . '; padding-top: ' . $field['margin'] . '; margin-bottom: ' . $field['margin'] . '"></div></div>';
        } else {
            $html .= $this->_addField($field_id, $field);
        }

        return $html;
    }

    /**
     * render individual form field
     * @param [string] $field_id   [field id to render]
     * @param [array]  $field      [actual field information]
     */
    protected function _addField($field_id, $field)
    {
        $attrs = [ 'required' => true, 'placeholder' => ' ' ];

        $attrs['class'] = 'validate';
        // if (in_array($field['type'], array( 'Text', 'Numeric', 'Select', 'Password' ))) {
        //     $attrs['class'] = 'inputMaterial validate';
        // } else {
        //     $attrs['class'] = 'validate';
        // }

        if (isset($field['attributes'])) {
            $attrs = array_merge($attrs, $field['attributes']);
        }

        $gridClass = 'medium-12 small-12';
        if (isset($field['grid'])) {
            $gridClass = $field['grid'];
        }

        if (isset($this->saved_data[$field_id]) && !isset($field['protected']) && (!isset($field['populate']) || $field['populate'])) {
            $attrs['value'] = $this->saved_data[$field_id];
        }

        if (isset($field['link_fields'])) {
            if ($field['link_fields'] === true) {
                // pass all saved data to the element
                $attrs['saved_data'] = $this->saved_data;
            } else {
                // pass only selected data
                foreach ($field['link_fields'] as $to_link_field => $link_field) {
                    if (isset($this->saved_data[$link_field])) {
                        $attrs[$to_link_field] = $this->saved_data[$link_field];
                    }
                }
            }
        }

        if (! isset($this->errors[$field_id])) {
            $field = $this->renderDecorated($field_id, $attrs, $gridClass);
        } else {
            $field = $this->renderDecoratedErrors($field_id, $this->errors[$field_id], $attrs, $gridClass);
        }

        return $field;
    }

    /**
     * render Label type fields - this is for caption fields
     * @param [type] $field      [description]
     */
    protected function _addLabel($field)
    {
        $hidden_field = false;
        if (isset($field['condition'])) {
            // condition `not` handler
            if (substr($field['condition'], 0, 1) == '!') {
                $field['condition'] = substr($field['condition'], 1);

                if (isset($this->saved_data[$field['condition']]) && $this->saved_data[$field['condition']]) {
                    $hidden_field = true;
                }
            } else {
                if (!isset($this->saved_data[$field['condition']]) || !$this->saved_data[$field['condition']]) {
                    return '';
                }
            }
        }

        if (isset($field['link_fields'])) {
            foreach ($field['link_fields'] as $link_field) {
                if (isset($this->saved_data[$link_field])) {
                    // first search for the field to link among the previous stored data
                    $field['label'] = str_replace('@' . $link_field, $this->saved_data[$link_field], $field['label']);
                } else {
                    // the link field not found among previous data
                    $data = 'N/A';
                    // switch ($link_field) {
                    //     case 'payment_info':
                    //         $data = $this->saved_data['card_brand'] . ' Ending in ' . $this->saved_data['card_last4'];
                    //         break;
                    // }
                    $field['label'] = str_replace('@' . $link_field, $data, $field['label']);
                }
            }
        }

        $gridClass = 'medium-12 small-12';
        if (isset($field['grid'])) {
            $gridClass = $field['grid'];
        }

        if ($hidden_field) {
            $gridClass .= ' hide';
        }

        $label = '<div class="columns ' . $gridClass . '">' . $field['label'] . '</div>';
        return $label;
    }

}
