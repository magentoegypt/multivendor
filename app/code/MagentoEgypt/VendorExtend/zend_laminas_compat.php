<?php
/**
 * Zend Framework to Laminas compatibility shim.
 *
 * Magento 2.4.5+ dropped laminas/laminas-zendframework-bridge, which used to
 * auto-alias legacy Zend\* classes to their Laminas\* equivalents. Some third
 * party modules (e.g. vnecoms/module-vendors-price-comparison) still reference
 * Zend\Stdlib\Parameters and trigger a fatal "Class not found" without the
 * aliases below.
 */
$aliases = [
    \Laminas\Stdlib\Parameters::class          => 'Zend\\Stdlib\\Parameters',
    \Laminas\Stdlib\ParametersInterface::class => 'Zend\\Stdlib\\ParametersInterface',
];

foreach ($aliases as $original => $legacy) {
    if (!class_exists($legacy, false) && !interface_exists($legacy, false)) {
        class_alias($original, $legacy);
    }
}
