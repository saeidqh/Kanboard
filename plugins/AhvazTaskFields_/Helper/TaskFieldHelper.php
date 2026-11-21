<?php

namespace Kanboard\Plugin\AhvazTaskFields\Helper;

use Kanboard\Core\Base;

class TaskFieldHelper extends Base
{
    /**
     * Server-side validation helper for Definition of Done + Reference rules
     * Call this from a listener or override if you want strict backend checks.
     */
    public function validateTaskValues(array &$values, array &$errors)
    {
        // Definition of Done is always required
        if (empty(trim($values['definition_of_done'] ?? ''))) {
            $errors['definition_of_done'][] = t('Definition of Done is required');
        }

        $categoryId = (int)($values['category_id'] ?? 0);

        // Reference / Document number / ... required for categories 1-5
        if ($categoryId >= 1 && $categoryId <= 5) {
            if (empty(trim($values['reference'] ?? ''))) {
                $label = $this->getReferenceLabel($categoryId);
                $errors['reference'][] = t('%s is required', $label);
            }
        }

        return empty($errors);
    }

    public function getReferenceLabel($categoryId)
    {
        $map = [
            1 => 'Document number',
            2 => 'Reference',
            3 => 'Letter number',
            4 => 'MOM number',
            5 => 'Department',
        ];

        return $map[(int)$categoryId] ?? 'Reference';
    }
}
