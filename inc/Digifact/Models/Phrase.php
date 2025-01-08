<?php

namespace Digifact\Models;

use Exception;

/**
 * Class Phrase
 *
 * Represents a phrase within a document, indicating special regimes and texts required in the DTE,
 * according to the taxpayer's affiliation and type of operation.
 */
class Phrase
{
    /** @var string Type of phrase */
    public $phraseType;

    /** @var string Scenario code */
    public $scenarioCode;

    /**
     * Phrase constructor.
     *
     * @param string $phraseType Type of phrase (default: '1')
     * @param string $scenarioCode Scenario code (default: '1')
     *
     * @throws Exception if any of the parameters are invalid
     */
    public function __construct($phraseType = '1', $scenarioCode = '1')
    {
        if (empty($phraseType)) {
            throw new Exception('Phrase type is required');
        }

        if (empty($scenarioCode)) {
            throw new Exception('Scenario code is required');
        }

        $this->phraseType = $phraseType;
        $this->scenarioCode = $scenarioCode;
    }
} // END class Phrase
