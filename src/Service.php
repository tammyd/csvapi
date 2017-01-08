<?php

namespace CSVAPI;

use CSVAPI\Exceptions\InvalidFilterException;
use CSVAPI\Exceptions\NoDataException;
use CSVAPI\Exceptions\NotImplementedException;
use CSVAPI\Utils\ArrayUtils;
use CSVAPI\Utils\CSVUtils;
use GuzzleHttp\ClientInterface;

/**
 * Class Service
 * @package CSVAPI
 */
class Service
{
    /**
     * @var string
     */
    protected $source = null;
    /**
     * @var string
     */
    protected $sourceFormat = 'csv';
    /**
     * @var string
     */
    protected $sort = null;
    /**
     * @var string
     */
    protected $sortDir = null;
    /**
     * @var string
     */
    protected $filter = null;
    /**
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * @var array
     */
    private $headers;

    /**
     * Service constructor.
     * @param ClientInterface $httpClient
     */
    public function __construct(ClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return array
     * @throws NoDataException
     * @throws NotImplementedException
     */
    public function getData() {

        $result = [];
        $data = $this->readDataFromSource([]);
        if (!$data) {
            throw new NoDataException();
        }

        switch ($this->getSourceFormat()) {
            case 'csv':
                $result = $this->parseCSVString($data); break;
            default:
                throw new NotImplementedException($this->getSourceFormat() . " source format not implemented");
        }

        $result = $this->filterData($result);
        $result = $this->sortData($result);

        return $result;

    }

    /**
     * @param string $data
     * @return array
     */
    protected function parseCSVString($data) {
        $data = CSVUtils::normalizeLineEndings($data);
        $lines = explode("\n", $data);
        $result = array_map('str_getcsv', $lines);
        $header = array_shift($result);
        $this->headers = $header;
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

    /**
     * @param array $row
     * @param string $key
     * @param array $header
     */
    protected function mergeHeader(array &$row, $key, array $header) {
        $row = array_combine($header, $row);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function sortData(array $data = []) {

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

    /**
     * @param array $data
     * @return array
     */
    protected function filterData(array $data = []) {

        if (!$data || !$this->getFilter()) {
            return $data;
        }
        $filter = $this->getFilter();

        //evaluate left to right.
        //X AND Y OR Z => (X AND Y) OR Z
        //A OR B OR C and D => ((A OR B) OR C) AND D
        list($filters, $operators) = $this->parseMultiFilter($filter);

        $prevResult = [];
        $currComp = "_OR_";
        foreach ($filters as $i=>$filter) {
            $subResult = $this->singleDataFilter($data, $filter);

            if (in_array($currComp, ['_or_', "_OR_"])) {
                $prevResult = array_merge($prevResult, $subResult);
            } else if (in_array($currComp, ['_and_', "_AND_"])) {

                $prevResult = array_map("unserialize",
                    array_intersect(ArrayUtils::serializeArrayValues($prevResult),ArrayUtils::serializeArrayValues($subResult)));
                array_walk($prevResult, [$this, 'mergeHeader'], $this->headers);
            }

            if ($i < count($filters) - 1) {
                $currComp = $operators[$i];
            }
        }

        return $prevResult;

    }

    /**
     * @param string $filter
     * @return array
     */
    protected function parseMultiFilter($filter) {
        $splitRegex = "/(_AND_|_and_|_or_|_OR_)/";
        $result = preg_split($splitRegex, $filter);
        $comps = [];


        foreach ($result as $i=>$rtI) {
            $j = $i+1;
            if (isset($result[$j])) {
                $rtJ = $result[$j];
                $regex = sprintf("/%s(.+?)%s/", $rtI, $rtJ);
                $matches = [];
                preg_match($regex, $filter, $matches);
                $comps[] = $matches[1];
            }

        }


        return [$result, $comps];


    }


    /**
     * @param $data
     * @param $filter
     * @return array
     * @throws InvalidFilterException
     */
    protected function singleDataFilter($data, $filter) {

        // Supports <, <=, =, >=, >, !=
        $matches = [];
        if (!preg_match("/^(.+?)(!=|=|<=|>=|<|>)(.+?)$/", $filter, $matches)) {
            throw new InvalidFilterException("Invalid filter $filter");
        }
        list($field, $comp, $value) = array_slice($matches, 1, 3);

        $data = array_filter($data, function($record) use ($field, $comp, $value) {

            switch ($comp) {
                case '=':
                    return $record[$field] == $value;
                case '!=':
                    return $record[$field] != $value;
                case '<':
                    return $record[$field] < $value;
                case '<=':
                    return $record[$field] <= $value;
                case '>=':
                    return $record[$field] >= $value;
                case '>':
                    return $record[$field] > $value;
            }

        });

        return $data;

    }

    /**
     * @param array $options
     * @return null|string
     */
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
     * @param string $source
     *
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = (string)$source;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSourceFormat()
    {
        return $this->sourceFormat;
    }

    /**
     * @param string $sourceFormat
     *
     * @return $this
     */
    public function setSourceFormat($sourceFormat)
    {
        if ($sourceFormat) {
            $this->sourceFormat = (string)$sourceFormat;
        }

        return $this;
    }

    /**
     * @param string $sort
     *
     * @return $this
     */
    public function setSort($sort)
    {
        $this->sort = (string) $sort;

        return $this;
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
     * @param string $sortDir
     *
     * @return $this
     */
    public function setSortDir($sortDir)
    {
        $this->sortDir = (string) $sortDir;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }


    /**
     * @param string $filter
     *
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->filter = (string)$filter;

        return $this;
    }
}