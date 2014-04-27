<?php

namespace Foolz\Foolframe\Model\Validation;

use Foolz\Foolframe\Model\Validation\ActiveConstraint\ActiveConstraint;
use Foolz\Foolframe\Model\Validation\Violation;
use Foolz\Foolframe\Model\Validation\ViolationCollection;
use Symfony\Component\Validator\Validation as SymfonyValidation;
use Symfony\Component\Validator\Constraints as Assert;

class Validator
{
    /**
     * @var array
     */
    protected $original_values = [];

    /**
     * @var \Symfony\Component\Validator\Constraints[]
     */
    protected $constraints = [];

    /**
     * @var array
     */
    protected $final_values = [];

    /**
     * @var array
     */
    protected $labels = [];

    /**
     * @var ViolationCollection
     */
    protected $violations;

    /**
     * @param string $field_name
     * @param string $label
     * @param \Symfony\Component\Validator\Constraints[]|\Symfony\Component\Validator\Constraints[] $constraints
     * @return $this
     */
    public function add($field_name, $label, $constraints) {
        $this->labels[$field_name] = $label;
        $this->constraints[$field_name] = $constraints;

        return $this;
    }

    /**
     * Runs the validation against the provided array of fields
     *
     * @param array $data
     */
    public function validate(Array $data) {
        $this->original_values = $data;
        $this->final_values = $data;

        $validator = SymfonyValidation::createValidator();
        $violations_arr = [];

        $this->violations = new ViolationCollection([]);

        foreach ($this->constraints as $key => $constraint) {
            // we always keep the variables set someway
            if (!isset($this->final_values[$key])) {
                $this->final_values[$key] = false;
            }

            // if it isn't an array and we just request a Trim
            if (!is_array($constraint) && $constraint instanceof ActiveConstraint) {
                $this->final_values[$key] = $constraint->run($this->final_values[$key]);
                // it's not a classic symfony constraint, get rid of it
                $constraint = [];
            }

            if (is_array($constraint)) {
                foreach ($constraint as $k => $c) {
                    if ($c instanceof ActiveConstraint) {
                        $this->final_values[$key] = $c->run($this->final_values[$key]);
                        // it's not a classic symfony constraint, get rid of it
                        unset($constraint[$k]);
                    }
                }
            }

            $violations = $validator->validateValue($this->final_values[$key], $constraint);
            if ($violations->count() > 0) {
                $violations_arr[$key] = new Violation($violations, $key, $this->labels[$key]);
            }

            $this->violations = new ViolationCollection($violations_arr);
        }
    }

    /**
     * Returns the array of values before they were treated by the validation
     *
     * @return array
     */
    public function getOriginalValues() {
        return $this->original_values;
    }

    /**
     * Gets the array of values after they were treated by the validation
     * @return array
     */
    public function getFinalValues() {
        return $this->final_values;
    }

    /**
     * Returns the violations generated by the validation process
     *
     * @return ViolationCollection
     */
    public function getViolations() {
        return $this->violations;
    }

    /**
     * Checks the form for and returns either a compiled array of values or
     * the error
     *
     * @param $form array
     * @param $alternate array name/value pairs to use instead of the POST array
     * @return array
     */
    public static function formValidate($form, $input)
    {
        // this gets a bit complex because we want to show all errors at the same
        // time, which means we have to run both core validation and custom, then
        // merge the result.

        foreach ($form as $name => $item) {
            if (isset($item['sub'])) {
                // flatten the form
                $form = array_merge($form, $item['sub']);
            }

            if (isset($item['sub_inverse'])) {
                // flatten the form
                $form = array_merge($form, $item['sub_inverse']);
            }

            if (isset($item['checkboxes'])) {
                // flatten the form
                $form_temp = array();

                foreach ($item['checkboxes'] as $checkbox) {
                    $form_temp[$name . ',' . $checkbox['array_key'] . ''] = $checkbox;
                }

                $form = array_merge($form, $form_temp);
            }
        }

        $validator = new static();

        foreach ($form as $name => $item) {
            if (isset($item['validation'])) {
                $validator->add($name, $item['label'], $item['validation']);
            }
        }

        $validator->validate($input);

        /*
            $constraint_arr = [];
            if (isset($item['validation'])) {
                // set the rules and add [] to the name if array
                $constraint_arr[$name . ((isset($item['array']) && $item['array']) ? '[]' : '')] = ;
            }
        }*/


        $validation_func = array();
        // we run this after form_validation in case form_validation edited the POST data
        foreach ($form as $name => $item) {
            // the "required" MUST be handled with the standard form_validation
            // or we'll never get in here
            if (isset($item['validation_func']) && isset($input[$name])) {
                // contains TRUE for success and in array with ['error'] in case
                $validation_func[$name] = $item['validation_func']($input, $form);

                // critical errors don't allow the continuation of the validation.
                // this allows less checks for functions that come after the critical ones.
                // criticals are usually the IDs in the hidden fields.
                if (isset($validation_func[$name]['critical']) && $validation_func[$name]['critical'] == true) {
                    break;
                }

                if (isset($validation_func[$name]['push']) && is_array($validation_func[$name]['push'] == true)) {
                    // overwrite the $input array
                    foreach ($validation_func[$name]['push'] as $n => $i) {
                        $input[$n] = $i;
                    }
                }
            }
        }

        // filter results, since the closures return ['success'] = TRUE on success
        $validation_func_errors = array();
        $validation_func_warnings = array();
        foreach ($validation_func as $item) {
            // we want only the errors
            if (isset($item['success'])) {
                continue;
            }

            if (isset($item['warning'])) {
                // we want only the human readable error
                $validation_func_warnings[] = $item['warning'];
            }

            if (isset($item['error'])) {
                // we want only the human readable error
                $validation_func_errors[] = $item['error'];
            }
        }

        if ($validator->getViolations()->count() || count($validation_func_errors)) {
            $errors = [];

            if ($validator->getViolations()->count()) {
                $errors[] = [$validator->getViolations()->getText()];
            }

            $errors = array_merge($errors, $validation_func_errors);

            return array('error' => implode("\n", $errors));
        } else {
            // get rid of all the uninteresting inputs and simplify
            $result = array();

            foreach ($form as $name => $item) {
                // not interested in data that is not related to database
                if ($item['type'] != 'checkbox_array' &&
                    (!isset($item['database']) || $item['database'] !== TRUE) &&
                    (!isset($item['preferences']) || $item['preferences'] === FALSE)
                ) {
                    continue;
                }

                if ($item['type'] == 'checkbox_array') {
                    foreach ($item['checkboxes'] as $checkbox_key => $checkbox) {
                        if (isset($input[$name][$checkbox['array_key']]) && $input[$name][$checkbox['array_key']] == 1) {
                            $result[$name][$checkbox['array_key']] = 1;
                        } else {
                            $result[$name][$checkbox['array_key']] = 0;
                        }
                    }
                } else if ($item['type'] == 'checkbox') {
                    if (isset($input[$name]) && $input[$name] == 1) {
                        $result[$name] = 1;
                    } else {
                        $result[$name] = 0;
                    }
                } else {
                    if (isset($input[$name]) && $input[$name] !== FALSE) {
                        $result[$name] = $input[$name];
                    }
                }
            }

            if (count($validation_func_warnings) > 0) {
                return array('success' => $result, 'warning' => implode(' ', $validation_func_warnings));
            }
            // returning a form with the new values
            return array('success' => $result);
        }
    }
}
