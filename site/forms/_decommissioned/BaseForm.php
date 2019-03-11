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

    /**
     * Returns the default value for field 'csrf'
     */
    public function renderCsrf()
    {
        return $this->renderItem($this->get('csrf'));
    }

    /**
     * Set Csrf
     */
    public function setCsrf()
    {
        $csrf = new Hidden(array(
            'name' => 'csrf',
            'value' => @$this->security->getToken()
        ));

        $csrf->addValidator(new Identical(array(
            'value' => $this->security->getSessionToken(),
            'message' => '<strong>CSRF</strong> security token is invalid.'
        )));

        $this->add($csrf);
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

        $html = '<div class="group columns ' . $gridClass . '">';
            $html .= $e->render($attr);

            if (get_class($e) == 'Phalcon\Forms\Element\Password') {
                $html .= '<a href="javascript:void(0);" class="toggle_pwd">Show</a>';
            }

            $html .= '<span class="highlight"></span>';
            $html .= '<span class="bar"></span>';
            $html .= '<label class="reg" for="' . $e->getName() . '">' . $e->getLabel() . '</label>';
            $html .= $abide;
            $html .= $tooltip;
        $html .= '</div>';

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

}
