<?php
declare(strict_types=1);
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagImportExport\Components\Transformers;

class SmartyExpressionEvaluator implements ExpressionEvaluator
{
    private \Shopware_Components_StringCompiler $compiler;

    public function __construct(
        \Enlight_Template_Manager $templateManager
    ) {
        $this->compiler = new \Shopware_Components_StringCompiler($templateManager);
    }

    /**
     * {@inheritDoc}
     */
    public function evaluate(string $expression, ?array $variables)
    {
        if (empty($expression)) {
            throw new \Exception('Empty expression in smarty evaluator');
        }

        if ($variables === null) {
            throw new \Exception('Invalid variables passed to smarty evaluator');
        }

        $this->convertPricesColumnsToFloat($variables);

        $evaluatedParam = $this->compiler->compileSmartyString($expression, $variables);

        return \trim($evaluatedParam);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function convertPricesColumnsToFloat(array &$variables): void
    {
        if (isset($variables['price'])) {
            $variables['price'] = (float) \str_replace(',', '.', $variables['price']);
        }
        if (isset($variables['pseudoPrice'])) {
            $variables['pseudoPrice'] = (float) \str_replace(',', '.', $variables['pseudoPrice']);
        }
        if (isset($variables['purchasePrice'])) {
            $variables['purchasePrice'] = (float) \str_replace(',', '.', $variables['purchasePrice']);
        }
        if (isset($variables['regulationPrice'])) {
            $variables['regulationPrice'] = (float) \str_replace(',', '.', $variables['regulationPrice']);
        }
    }
}
