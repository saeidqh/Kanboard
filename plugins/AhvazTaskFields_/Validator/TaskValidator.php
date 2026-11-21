<?php

namespace Kanboard\Plugin\AhvazTaskFields\Validator;

use Kanboard\Validator\TaskValidator as BaseTaskValidator;

/**
 * Extend core TaskValidator so required errors render like Title.
 */
class TaskValidator extends BaseTaskValidator
{
    public function validateCreation(array $values)
    {
        list($valid, $errors) = parent::validateCreation($values);
        return $this->applyAhvazRules($values, $valid, $errors);
    }

    public function validateModification(array $values)
    {
        list($valid, $errors) = parent::validateModification($values);
        return $this->applyAhvazRules($values, $valid, $errors);
    }

    protected function applyAhvazRules(array $values, $valid, array $errors)
    {
        // Definition of Done
        $dod = isset($values['definition_of_done']) ? trim((string) $values['definition_of_done']) : '';
        if ($dod === '') {
            $valid = false;
            $errors['definition_of_done'][] = t('Definition of Done is required');
        }

        // Category (No category = empty / 0)
        $cat = isset($values['category_id']) ? $values['category_id'] : null;
        if ($cat === null || $cat === '' || $cat === '0' || $cat === 0) {
            $valid = false;
            $errors['category_id'][] = t('The category is required');
        }

        // Reference (dynamic label field)
        $ref = isset($values['reference']) ? trim((string) $values['reference']) : '';
        if ($ref === '') {
            $valid = false;
            $errors['reference'][] = t('The reference is required');
        }

        return array($valid, $errors);
    }
}
