<?php

namespace Bigfoot\Bundle\ImportBundle\Extractor;

/**
 * Class XmlExtractor
 *
 * @package Bigfoot\Bundle\ImportBundle\Extractor
 */
class XmlExtractor
{
    /**
     * @param string|\DOMDocument $input
     * @param string              $xpath
     * @param array               $namespaces
     *
     * @return string
     */
    public static function extract(&$input, $xpath, $namespaces = array())
    {
        $previousErrorSetting = null;

        if ($input instanceof \DOMDocument) {
            $dom = $input;
        } else {
            $dom = new \DOMDocument();
            $previousErrorSetting = libxml_use_internal_errors(false);
            set_error_handler(
                function ($errno, $errstr) {
                    // Suppresses warning
                },
                E_WARNING
            );
            $dom->loadXML($input, LIBXML_VERSION >= 20900 ? LIBXML_PARSEHUGE : null);
            restore_error_handler();
        }

        $domXpath = new \DOMXPath($dom);

        foreach ($namespaces as $prefix => $namespace) {
            $domXpath->registerNamespace($prefix, $namespace);
        }

        $nodes   = $domXpath->query($xpath);
        $content = '';

        /** @var \DOMNode $node */
        foreach ($nodes as $node) {
            $content .= $dom->saveXML($node);
            $node->parentNode->removeChild($node);
        }

        if (is_string($input)) {
            $input = $dom->saveXML($dom->documentElement);
        }

        if (null !== $previousErrorSetting) {
            libxml_use_internal_errors($previousErrorSetting);
        }

        return $content;
    }
}
