<?php

namespace Kanboard\Model;

use Kanboard\Core\Base;

/**
 * Color model
 *
 * @package  Kanboard\Model
 * @author   Frederic Guillot
 */
class ColorModel extends Base
{
    /**
     * Default colors
     *
     * @access protected
     * @var array
     */
    protected $default_colors = array(
        'yellow' => array(
            'name' => 'Yellow',
            'background' => 'rgb(245, 247, 196)',
            'border' => 'rgb(223, 227, 45)',
        ),
        'blue' => array(
            'name' => 'Blue',
            'background' => 'rgb(219, 235, 255)',
            'border' => 'rgb(168, 207, 255)',
        ),
        'green' => array(
            'name' => 'Green',
            'background' => 'rgb(189, 244, 203)',
            'border' => 'rgb(74, 227, 113)',
        ),
        'purple' => array(
            'name' => 'Purple',
            'background' => 'rgb(223, 176, 255)',
            'border' => 'rgb(205, 133, 254)',
        ),
        'red' => array(
            'name' => 'Red',
            'background' => 'rgb(255, 187, 187)',
            'border' => 'rgb(255, 151, 151)',
        ),
        'orange' => array(
            'name' => 'Orange',
            'background' => 'rgb(255, 215, 179)',
            'border' => 'rgb(255, 172, 98)',
        ),
        'grey' => array(
            'name' => 'Grey',
            'background' => 'rgb(238, 238, 238)',
            'border' => 'rgb(204, 204, 204)',
        ),
        'brown' => array(
            'name' => 'Brown',
            'background' => '#d7ccc8',
            'border' => '#4e342e',
        ),
        'deep_orange' => array(
            'name' => 'Deep Orange',
            'background' => '#ffab91',
            'border' => '#e64a19',
        ),
        'dark_grey' => array(
            'name' => 'Dark Grey',
            'background' => '#cfd8dc',
            'border' => '#455a64',
        ),
        'pink' => array(
            'name' => 'Pink',
            'background' => '#f48fb1',
            'border' => '#d81b60',
        ),
        'teal' => array(
            'name' => 'Teal',
            'background' => '#80cbc4',
            'border' => '#00695c',
        ),
        'cyan' => array(
            'name' => 'Cyan',
            'background' => '#b2ebf2',
            'border' => '#00bcd4',
        ),
        'lime' => array(
            'name' => 'Lime',
            'background' => '#e6ee9c',
            'border' => '#afb42b',
        ),
        'light_green' => array(
            'name' => 'Light Green',
            'background' => '#dcedc8',
            'border' => '#689f38',
        ),
        'amber' => array(
            'name' => 'Amber',
            'background' => '#ffe082',
            'border' => '#ffa000',
        ),
        'indigo' => array(
            'name' => 'Indigo',
            'background' => '#c5cae9',
            'border' => '#3f51b5',
        ),
        'deep_purple' => array(
            'name' => 'Deep Purple',
            'background' => '#d1c4e9',
            'border' => '#673ab7',
        ),
        'light_blue' => array(
            'name' => 'Light Blue',
            'background' => '#b3e5fc',
            'border' => '#0288d1',
        ),
        'light_cyan' => array(
            'name' => 'Light Cyan',
            'background' => '#e0f7fa',
            'border' => '#00acc1',
        ),
        'blue_grey' => array(
            'name' => 'Blue Grey',
            'background' => '#b0bec5',
            'border' => '#455a64',
        ),
        'warm_grey' => array(
            'name' => 'Warm Grey',
            'background' => '#e0e0e0',
            'border' => '#757575',
        ),
        'sand' => array(
            'name' => 'Sand',
            'background' => '#f5e6c8',
            'border' => '#c2a878',
        ),
        'peach' => array(
            'name' => 'Peach',
            'background' => '#ffe0b2',
            'border' => '#fb8c00',
        ),
        'coral' => array(
            'name' => 'Coral',
            'background' => '#ffccbc',
            'border' => '#ff5722',
        ),
        'lavender' => array(
            'name' => 'Lavender',
            'background' => '#e1bee7',
            'border' => '#8e24aa',
        ),
        'violet' => array(
            'name' => 'Violet',
            'background' => '#d1c4e9',
            'border' => '#5e35b1',
        ),
        'sky' => array(
            'name' => 'Sky',
            'background' => '#b3e5fc',
            'border' => '#039be5',
        ),
        'mint' => array(
            'name' => 'Mint',
            'background' => '#b2dfdb',
            'border' => '#00897b',
        ),
        'olive' => array(
            'name' => 'Olive',
            'background' => '#e6ee9c',
            'border' => '#9e9d24',
        ),
        'gold' => array(
            'name' => 'Gold',
            'background' => '#fff59d',
            'border' => '#fbc02d',
        ),
        'bronze' => array(
            'name' => 'Bronze',
            'background' => '#d7ccc8',
            'border' => '#8d6e63',
        ),
        'slate' => array(
            'name' => 'Slate',
            'background' => '#cfd8dc',
            'border' => '#546e7a',
        ),
        'mauve' => array(
            'name' => 'Mauve',
            'background' => '#f3e5f5',
            'border' => '#ab47bc',
        ),
        'apricot' => array(
            'name' => 'Apricot',
            'background' => '#ffe0b2',
            'border' => '#ff9800',
        ),
        'seafoam' => array(
            'name' => 'Seafoam',
            'background' => '#b2dfdb',
            'border' => '#26a69a',
        ),
        'periwinkle' => array(
            'name' => 'Periwinkle',
            'background' => '#c5cae9',
            'border' => '#5c6bc0',
        ),
        'taupe' => array(
            'name' => 'Taupe',
            'background' => '#d7ccc8',
            'border' => '#6d4c41',
        ),
        'champagne' => array(
            'name' => 'Champagne',
            'background' => '#fff8e1',
            'border' => '#ffb300',
        ),
        'lilac' => array(
            'name' => 'Lilac',
            'background' => '#e1bee7',
            'border' => '#9c27b0',
        ),
        'aqua' => array(
            'name' => 'Aqua',
            'background' => '#b2ebf2',
            'border' => '#00acc1',
        ),
        'mustard' => array(
            'name' => 'Mustard',
            'background' => '#fff59d',
            'border' => '#f9a825',
        ),
        'plum' => array(
            'name' => 'Plum',
            'background' => '#e1bee7',
            'border' => '#7b1fa2',
        ),
        'steel' => array(
            'name' => 'Steel',
            'background' => '#cfd8dc',
            'border' => '#37474f',
        ),
        'ivory' => array(
            'name' => 'Ivory',
            'background' => '#fafafa',
            'border' => '#bdbdbd',
        ),
    );

    /**
     * Find a color id from the name or the id
     *
     * @access public
     * @param  string  $color
     * @return string
     */
    public function find($color)
    {
        $color = strtolower($color);

        foreach ($this->default_colors as $color_id => $params) {
            if ($color_id === $color) {
                return $color_id;
            } elseif ($color === strtolower($params['name'])) {
                return $color_id;
            }
        }

        return '';
    }

    /**
     * Get color properties
     *
     * @access public
     * @param  string  $color_id
     * @return array
     */
    public function getColorProperties($color_id)
    {
        if (isset($this->default_colors[$color_id])) {
            return $this->default_colors[$color_id];
        }

        return $this->default_colors[$this->getDefaultColor()];
    }

    /**
     * Get available colors
     *
     * @access public
     * @param  bool $prepend
     * @return array
     */
    public function getList($prepend = false)
    {
        $listing = $prepend ? array('' => t('All colors')) : array();

        foreach ($this->default_colors as $color_id => $color) {
            $listing[$color_id] = t($color['name']);
        }

        $this->hook->reference('model:color:get-list', $listing);

        return $listing;
    }

    /**
     * Get the default color
     *
     * @access public
     * @return string
     */
    public function getDefaultColor()
    {
        return $this->configModel->get('default_color', 'yellow');
    }

    /**
     * Get the default colors
     *
     * @access public
     * @return array
     */
    public function getDefaultColors()
    {
        return $this->default_colors;
    }

    /**
     * Get border color from string
     *
     * @access public
     * @param  string   $color_id   Color id
     * @return string
     */
    public function getBorderColor($color_id)
    {
        $color = $this->getColorProperties($color_id);
        return $color['border'];
    }

    /**
     * Get background color from the color_id
     *
     * @access public
     * @param  string   $color_id   Color id
     * @return string
     */
    public function getBackgroundColor($color_id)
    {
        $color = $this->getColorProperties($color_id);
        return $color['background'];
    }

    /**
     * Get CSS stylesheet of all colors
     *
     * @access public
     * @return string
     */
    public function getCss()
    {
        $buffer = '';

        foreach ($this->default_colors as $color => $values) {
            $buffer .= '.task-board.color-'.$color.', .task-summary-container.color-'.$color.', .color-picker-square.color-'.$color.', .task-board-category.color-'.$color.', .table-list-category.color-'.$color.', .task-tag.color-'.$color.' {';
            $buffer .= 'background-color: '.$values['background'].';';
            $buffer .= 'border-color: '.$values['border'];
            $buffer .= '}';
            $buffer .= 'td.color-'.$color.' { background-color: '.$values['background'].'}';
            $buffer .= '.table-list-row.color-'.$color.' {border-left: 5px solid '.$values['border'].'}';
        }

        return $buffer;
    }
}
