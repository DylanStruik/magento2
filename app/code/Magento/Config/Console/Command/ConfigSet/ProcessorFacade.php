<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Config\Console\Command\ConfigSet;

use Magento\Config\Console\Command\ConfigSetCommand;
use Magento\Framework\App\Scope\ValidatorInterface;
use Magento\Config\Model\Config\PathValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\RuntimeException;

/**
 * Processor facade for config:set command.
 *
 * @see ConfigSetCommand
 */
class ProcessorFacade
{
    /**
     * The scope and scope code validator.
     *
     * Checks if scope and scope code exist, and scope code belongs to scope.
     * For example, scope "websites" and scope code "base" exist, and scope code "base" belongs to scope "website".
     *
     * @var ValidatorInterface
     */
    private $scopeValidator;

    /**
     * The path validator.
     *
     * Checks whether the config path present in configuration structure.
     *
     * @var PathValidator
     */
    private $pathValidator;

    /**
     * The factory for config:set processors.
     *
     * @var ConfigSetProcessorFactory
     */
    private $configSetProcessorFactory;

    /**
     * @param ValidatorInterface $scopeValidator The scope validator
     * @param PathValidator $pathValidator The path validator
     * @param ConfigSetProcessorFactory $configSetProcessorFactory The factory for config:set processors
     */
    public function __construct(
        ValidatorInterface $scopeValidator,
        PathValidator $pathValidator,
        ConfigSetProcessorFactory $configSetProcessorFactory
    ) {
        $this->scopeValidator = $scopeValidator;
        $this->pathValidator = $pathValidator;
        $this->configSetProcessorFactory = $configSetProcessorFactory;
    }

    /**
     * Processes config:set command.
     *
     * @param string $path The configuration path in format group/section/field_name
     * @param string $value The configuration value
     * @param string $scope The configuration scope (default, website, or store)
     * @param string $scopeCode The scope code
     * @param boolean $lock The lock flag
     * @return string Processor response message
     * @throws RuntimeException If some validation is wrong or cannot save config value
     */
    public function process($path, $value, $scope, $scopeCode, $lock)
    {
        try {
            $this->scopeValidator->isValid($scope, $scopeCode);
            $this->pathValidator->validate($path);

            $processor = $lock
                ? $this->configSetProcessorFactory->create(ConfigSetProcessorFactory::TYPE_LOCK)
                : $this->configSetProcessorFactory->create(ConfigSetProcessorFactory::TYPE_DEFAULT);
            $message = $lock
                ? 'Value was saved and locked.'
                : 'Value was saved.';

            // The processing flow depends on --lock option.
            $processor->process($path, $value, $scope, $scopeCode);

            return $message;
        } catch (LocalizedException $exception) {
            throw new RuntimeException(__($exception->getMessage()), $exception);
        }
    }
}
