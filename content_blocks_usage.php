<?php

require_once dirname(__FILE__) . '/config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();

$modx->initialize('mgr');

$c = $modx->newQuery('modResource');
$c->where(
    [
        'published' => 1,
        'deleted' => 0,
        'JSON_CONTAINS(properties, \'true\', \'$.contentblocks._isContentBlocks\')'
    ]
);

$resources = $modx->getIterator('modResource', $c);

$cb = [
    'layouts' => [],
    'fields' => []
];

if (!function_exists('getLayoutsFromContent')) {
    function getLayoutsFromContent($content) {
        $layouts = [];
        $content = json_decode($content, true);
        foreach ($content as $block) {
            $layouts[] = $block['layout'];
        }
        return $layouts;
    }
}

if (!function_exists('getFieldsandLayoutsFromLinear')) {
    function getFieldsandLayoutsFromLinear($linear, &$fields, &$layouts) {
        foreach($linear as $block) {
            if (isset($block['rows'])) {
                getFieldsandLayoutsFromLinear($block['rows'], $fields, $layouts);
            }
            if (isset($block['child_layouts'])) {
                getFieldsandLayoutsFromLinear($block['child_layouts'], $fields, $layouts);
            }
            if (isset($block['content'])) {
                foreach($block['content'] as $content) {
                    getFieldsandLayoutsFromLinear($content, $fields, $layouts);
                }
            }
            if (isset($block['layout'])) {
                if (!in_array($block['layout'], $layouts)) {
                    $layouts[] = $block['layout'];
                }
            }
            if (isset($block['field'])) {
                if (!in_array($block['field'], $fields)) {
                    $fields[] = $block['field'];
                }
            }
        }
    }
}

foreach ($resources as $resource) {
    $properties = $resource->get('properties');
    $contentBlocks = $properties['contentblocks'];
    $layouts = getLayoutsFromContent($cb['content']);
    foreach ($layouts as $layout) {
        if (!in_array($layout, $cb['layouts'])) {
            $cb['layouts'][] = $layout;
        }
    }
    $fields = $contentBlocks['fieldsCount'];
    foreach ($fields as $field => $count) {
        if (!in_array($field, $cb['fields'])) {
            $cb['fields'][] = $field;
        }
    }
    getFieldsandLayoutsFromLinear($contentBlocks['linear'], $cb['fields'], $cb['layouts']);

}

usort($cb['fields'], function($a, $b) {
    // sort by integer value
    return (int)$a - (int)$b;
});

usort($cb['layouts'], function($a, $b) {
    // sort by integer value
    return (int)$a - (int)$b;
});

echo "Layouts:\n";
echo implode(", ", $cb['layouts']);

echo "\n\nFields:\n";
echo implode(", ", $cb['fields']);
echo "\n";