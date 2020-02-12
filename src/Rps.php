<?php

namespace NFePHP\NFSeProdam;

/**
 * Class for RPS construction and validation of data
 *
 * @category  NFePHP
 * @package   NFePHP\NFSeProdam
 * @copyright NFePHP Copyright (c) 2020
 * @license   http://www.gnu.org/licenses/lgpl.txt LGPLv3+
 * @license   https://opensource.org/licenses/MIT MIT
 * @license   http://www.gnu.org/licenses/gpl.txt GPLv3+
 * @author    Roberto L. Machado <linux.rlm at gmail dot com>
 * @link      http://github.com/nfephp-org/sped-nfse-prodam for the canonical source repository
 */

use stdClass;
use NFePHP\Common\Certificate;
use NFePHP\NFSeProdam\RpsInterface;
use NFePHP\NFSeProdam\Common\Factory;
use JsonSchema\Validator as JsonValid;

class Rps implements RpsInterface
{
    /**
     * @var stdClass
     */
    public $std;
    /**
     * @var string
     */
    protected $ver;
    /**
     * @var string
     */
    protected $jsonschema;
    /**
     * @var \stdClass
     */
    protected $config;
    /**
     * @var Certificate
     */
    protected $certificate;


    /**
     * Constructor
     * @param stdClass $rps
     */
    public function __construct(stdClass $rps = null)
    {
        $this->init($rps);
    }
    
    /**
     * Add config
     * @param stdClass $config
     */
    public function config(\stdClass $config)
    {
        $this->config = $config;
    }
    
    public function addCertificate(Certificate $cert)
    {
        $this->certificate = $cert;
    }
    
    /**
     * {@inheritdoc}
     */
    public function render(stdClass $rps = null)
    {
        $this->init($rps);
        $fac = new Factory($this->std);
        if (!empty($this->config)) {
            $fac->addConfig($this->config);
            $fac->addCertificate($this->certificate);
        }
        return $fac->render();
    }
    
    /**
     * Inicialize properties and valid input
     * @param stdClass $rps
     */
    private function init(stdClass $rps = null)
    {
        if (!empty($rps)) {
            $this->std = $this->propertiesToLower($rps);
            if (empty($rps->version)) {
                $rps->version = '2.02';
            }
            $ver = str_replace('.', '_', $rps->version);
            $this->jsonschema = realpath("../storage/jsonSchemes/v$ver/rps.schema");
            $this->validInputData($this->std);
        }
    }
    
    /**
     * Change properties names of stdClass to lower case
     * @param stdClass $data
     * @return stdClass
     */
    public static function propertiesToLower(stdClass $data)
    {
        $properties = get_object_vars($data);
        $clone = new stdClass();
        foreach ($properties as $key => $value) {
            if ($value instanceof stdClass) {
                $value = self::propertiesToLower($value);
            }
            $nk = strtolower($key);
            $clone->{$nk} = $value;
        }
        return $clone;
    }

    /**
     * Validation json data from json Schema
     * @param stdClass $data
     * @return boolean
     * @throws \RuntimeException
     */
    protected function validInputData($data)
    {
        if (!is_file($this->jsonschema)) {
            return true;
        }
        $validator = new JsonValid();
        $validator->check($data, (object)['$ref' => 'file://' . $this->jsonschema]);
        if (!$validator->isValid()) {
            $msg = "";
            foreach ($validator->getErrors() as $error) {
                $msg .= sprintf("[%s] %s\n", $error['property'], $error['message']);
            }
            throw new \InvalidArgumentException($msg);
        }
        return true;
    }
}
