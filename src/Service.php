<?php

namespace CSVAPI;

use CSVAPI\Exceptions\NoDataException;
use CSVAPI\Exceptions\NotImplementedException;
use CSVAPI\Utils\ArrayUtils;
use CSVAPI\Utils\CSVUtils;
use GuzzleHttp\ClientInterface;

class Service
{
    protected $source = null;
    protected $sourceFormat = 'csv';
    protected $sort = null;
    protected $sortDir = null;

    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * Service constructor.
     * @param ClientInterface $httpClient
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getData() {

        $result = [];
        $data = $this->readDataFromSource([]);
        if (!$data) {
            throw new NoDataException();
        }

        switch ($this->getSourceFormat()) {
            case 'csv':
                $result = $this->parseCSV($data); break;
            default:
                throw new NotImplementedException($this->getSourceFormat() . " source format not implemented");
        }

        $result = $this->sortData($result);

        return $result;

    }

    protected function parseCSV($data) {
        $data = CSVUtils::normalizeLineEndings($data);
        $lines = explode("\n", $data);
        $result = array_map('str_getcsv', $lines);
        $header = array_shift($result);
        $headerLength = count($header);
        $result = array_filter($result, function($row) use ($headerLength) {
            if (!$row || !is_array($row) || count($row) != $headerLength) {
                return false;
            }
            return true;
        });

        array_walk($result, [$this, 'mergeHeader'], $header);
        return $result;
    }

    protected function mergeHeader(&$row, $key, $header) {
        $row = array_combine($header, $row);
    }

    protected function sortData(array $data = null) {

        usort($data, function($lineA, $lineB) {

            if (!$this->getSort()) return 0;
            $a = ArrayUtils::getArrayValue($lineA, $this->getSort());
            $b = ArrayUtils::getArrayValue($lineB, $this->getSort());

            if ($a == $b) {
                return 0;
            }

            if ($this->getSortDir() == 'desc') {
                return ($a < $b) ? 1 : -1;
            }
            return ($a < $b) ? -1 : 1;

        });

        return $data;

    }

    protected function readDataFromSource($options = []) {

        //@TODO - add caching layer. 'useCache' should be a possible option

        if (!$this->source) {
            return null;
        }

        $data = $this->httpClient->request('GET', $this->source);
        if ($data->getStatusCode() !== 200) {
            return null;
        }

        return (string)$data->getBody();
    }

    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return string|null
     */
    public function getSourceFormat()
    {
        return $this->sourceFormat;
    }

    /**
     * @return string|null
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @return string|null
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @return string|null
     */
    public function getSortDir()
    {
        return $this->sortDir;
    }

    /**
     * @param $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * @param $sourceFormat
     * @return $this
     */
    public function setSourceFormat($sourceFormat)
    {
        if ($sourceFormat) {
            $this->sourceFormat = $sourceFormat;
        }

        return $this;
    }


    /**
     * @param $sort
     * @return $this
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }


    /**
     * @param $sortDir
     * @return $this
     */
    public function setSortDir($sortDir)
    {
        $this->sortDir = $sortDir;

        return $this;
    }




}