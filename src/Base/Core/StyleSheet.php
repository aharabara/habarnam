<?php

namespace Base;

use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\Value\RuleValueList;
use Sabberworm\CSS\Value\Size;

class StyleSheet
{
    /**
     * @var Rule[][]
     */
    protected $styles = [];

    public function __construct(string $path)
    {
        $parser = new Parser(file_get_contents(dirname($_SERVER['SCRIPT_FILENAME']) . '/' . ltrim($path, './')));
        $document = $parser->parse();

        /* @var DeclarationBlock[] $rules */
        $rules = $document->getAllDeclarationBlocks();
        foreach ($rules as $rule) {
            foreach ($rule->getSelectors() as $selector) {
                $this->styles[$selector->getSelector()] = $rule->getRulesAssoc();
            }
        }
//        var_export(array_keys($this->styles)); die();
    }

    /**
     * @param string $selector
     * @return array
     */
    public function propertiesBySelector(string $selector): array
    {
        $bgColor = null;
        $textColor = null;
        $props = [];
        foreach ($this->styles[$selector] ?? [] as $style) {
            $props[$style->getRule()] = $style->getValue();
            switch ($style->getRule()) {
                case 'color':
                    $textColor = strtolower($style->getValue());
                    break;
                case 'background':
                case 'background-color':
                    $bgColor = strtolower($style->getValue());
                    break;
                case 'padding':
                case 'margin':
                    /** @var RuleValueList $value */
                    $value = $style->getValue();
                    $sizes = array_map(static function (Size $size) {
                        return $size->getSize();
                    }, $value->getListComponents());
                    
                    $props[$style->getRule()] = $sizes;
                    break;
            }
        }

        if (empty($textColor)) {
            $textColor = $this->closestParentAttributeValue($selector, 'color') ?? 'white';
        }
        if (empty($bgColor)) {
            $bgColor = $this->closestParentAttributeValue($selector, 'background-color') ?? 'black';
        }
        if (strpos($selector, ":focus")){
            var_export("{$bgColor}_{$textColor}");
            die();
        }
        $props['color-pair'] = constant(Colors::class . '::' . strtoupper("{$bgColor}_{$textColor}"));
        return $props; 
    }

    /**
     * @param string $selector
     * @param string $property
     * @return string|null
     */
    protected function closestParentAttributeValue(string $selector, string $property)
    {
        [$base, $states] = self::selectorWithStates($selector);
        $delimiter = ' > ';
        $parts = explode($delimiter, $base);
        while ($selector = implode($delimiter, $parts)) {
            foreach ($this->styles[$selector] ?? [] as $style) {
                if ($style->getRule() === $property) {
                    return $style->getValue();
                }
            }
            array_pop($parts);
        }
        return null;
    }

    /**
     * @param string $selector
     * @return array
     */
    public static function selectorWithStates(string $selector): array
    {
        $states = explode(':', $selector);
        $base = array_shift($states);
        return [$base, $states];
    }
}