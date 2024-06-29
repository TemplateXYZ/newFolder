<?php

class np {
    public static function array($data, $dtype = 'int') {
        $arr = new NumpyArray($data, $dtype);
        return $arr;
    }

    public static $version = "0.5.3";

    public static function random() {
        return new Random();
    }
}

class NumpyArray {
    private $data;
    private $dtype;

    public function __construct($data, $dtype = 'int') {
        if (is_array($data)) {
            $this->dtype = $dtype;
            $this->data = $this->convertToType($data, $dtype);
        } else {
            throw new Exception("Input must be an array");
        }
    }

    public function __get($key) {
        if (is_int($key)) {
            return $this->data[$key];
        } elseif (is_string($key)) {
            if (strpos($key, ',') !== false) {
                // Handle multiple indices like arr[i,j,k,...]
                $indices = explode(',', $key);
                $result = [];
                foreach ($indices as $index) {
                    $result[] = $this->data[$index];
                }
                return new NumpyArray($result, $this->dtype);
            } elseif (strpos($key, ':') !== false) {
                // Handle slicing like arr[1:5], arr[4:], arr[:4], arr[-3:-1], arr[1:5:2], arr[::2], etc.
                list($start, $end, $step) = array_map('intval', explode(':', $key));
                $slicedData = array_slice($this->data, $start, $end - $start, $step);
                return new NumpyArray($slicedData, $this->dtype);
            }
        }
        throw new Exception("Unsupported indexing");
    }

    public function dtype() {
        return $this->dtype;
    }

    public function astype($newDtype) {
        $newData = $this->convertToType($this->data, $newDtype);
        return new NumpyArray($newData, $newDtype);
    }

    public function copy() {
        return new NumpyArray($this->data, $this->dtype);
    }

    public function view() {
        return $this;
    }

    public function base() {
        return null;
    }

    public function shape() {
        return json_encode([count($this->data)]);
    }

    public function reshape($shape) {
        $flattened = array_values($this->data);
        $reshaped = [];

        // Calculate the total size of the new shape
        $totalSize = 1;
        foreach ($shape as $size) {
            $totalSize *= $size;
        }

        if (count($flattened) !== $totalSize) {
            throw new Exception("Cannot reshape array of size " . count($flattened) . " into shape " . json_encode($shape));
        }

        $currentIndex = 0;
        foreach ($shape as $size) {
            $subarray = array_slice($flattened, $currentIndex, $size);
            $reshaped[] = $subarray;
            $currentIndex += $size;
        }

        return new NumpyArray($reshaped, $this->dtype);
    }

    public function __toString() {
        return json_encode($this->data);
    }

    public function toJSON() {
        return json_encode($this->data);
    }

    public function iterate() {
        return new NumpyArrayIterator($this->data);
    }

    public function nditer() {
        return new NumpyArrayIterator($this->data);
    }

    public function split($num) {
        $chunks = array_chunk($this->data, ceil(count($this->data) / $num));
        $result = [];
        foreach ($chunks as $chunk) {
            $result[] = new NumpyArray($chunk, $this->dtype);
        }
        return $result;
    }

    public function where($condition) {
        $result = [];
        foreach ($this->data as $idx => $value) {
            if ($condition($value)) {
                $result[] = $idx;
            }
        }
        return new NumpyArray($result, 'int');
    }

    public function search($value) {
        $result = [];
        foreach ($this->data as $idx => $item) {
            if ($item === $value) {
                $result[] = $idx;
            }
        }
        return new NumpyArray($result, 'int');
    }

    public function sort() {
        $sortedData = $this->data;
        sort($sortedData);
        return new NumpyArray($sortedData, $this->dtype);
    }

    public function filter($condition) {
        $filteredData = array_filter($this->data, $condition);
        return new NumpyArray(array_values($filteredData), $this->dtype);
    }
}

class NumpyArrayIterator implements Iterator {
    private $data;
    private $position = 0;

    public function __construct($data) {
        $this->data = $data;
        $this->position = 0;
    }

    public function rewind() {
        $this->position = 0;
    }

    public function current() {
        return $this->data[$this->position];
    }

    public function key() {
        return $this->position;
    }

    public function next() {
        ++$this->position;
    }

    public function valid() {
        return isset($this->data[$this->position]);
    }
}

class Random {
    public function randint($low, $high = null, $size = null) {
        if ($high === null) {
            $high = $low;
            $low = 0;
        }
        
        if ($size === null) {
            return mt_rand($low, $high);
        }
        
        $result = [];
        for ($i = 0; $i < $size; $i++) {
            $result[] = mt_rand($low, $high);
        }
        
        return np::array($result);
    }

    public function rand($size = null) {
        if ($size === null) {
            return mt_rand() / mt_getrandmax();
        }
        
        $result = [];
        for ($i = 0; $i < $size; $i++) {
            $result[] = mt_rand() / mt_getrandmax();
        }
        
        return np::array($result);
    }

    public function choice($array, $size = null) {
        if ($size === null) {
            return $array[mt_rand(0, count($array) - 1)];
        }
        
        $result = [];
        for ($i = 0; $i < $size; $i++) {
            $result[] = $array[mt_rand(0, count($array) - 1)];
        }
        
        return np::array($result);
    }
}

// Custom "reon" loop function
function reon($arr, $callback) {
    if ($arr instanceof NumpyArray) {
        $iterator = $arr->iterate();
        foreach ($iterator as $value) {
            $callback($value);
        }
    }
}

// Example usage:
$arr = np::array([1, 2, 3, 4, 5, 6, 7], 'int');
echo "NumPy version: " . np::$version . "\n";

// Sorting example
$sorted = $arr->sort();
echo "Sorted array: " . $sorted . "\n";

// Filtering example
$condition = function ($value) {
    return $value % 2 == 0;
};
$filtered = $arr->filter($condition);
echo "Filtered array (even numbers): " . $filtered . "\n";

$random = np::random();

// randint examples
echo "Random integer between 0 and 10: " . $random->randint(10) . "\n";
echo "Random integer between 5 and 15: " . $random->randint(5, 15) . "\n";
echo "Random integers between 0 and 100, size 5: " . $random->randint(100, 5) . "\n";

// rand examples
echo "Random float between 0 and 1: " . $random->rand() . "\n";
echo "Random floats between 0 and 1, size 5: " . $random->rand(5) . "\n";

// choice examples
echo "Random choice from [3, 5, 7, 9]: " . $random->choice([3, 5, 7, 9]) . "\n";
echo "Random choices from [3, 5, 7, 9], size (3, 5): " . $random->choice([3, 5, 7, 9], [3, 5]) . "\n";


?>