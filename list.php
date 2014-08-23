<?PHP

namespace SAS;

class DoublyLinkedList_Node {
    private $data;
    private $prev;
    private $next;

    private $_id;

    public function __construct($data) {
        if(isset($data->_id)) {
            $this->_id = $data->_id;
        }
        else if(isset($data->id)) {
            $this->_id = $data->id;
        }
        else if(isset($data['id'])) {
            $this->_id = $data['id'];
        }
        else {
            $this->_id = $data;
        }

        $this->data = is_array($data) ? (object) $data : $data;
        $this->prev = isset($data->prev) ? $data->prev : null;
        $this->next = isset($data->next) ? $data->next : null;
    }

    public function __get($name) {
        $method_name = 'get'.ucfirst($name);

        if(method_exists($this, $method_name)) {
            return $this->$method_name();
        }

        return $this->$name;
    }

    public function getId() {
        return $this->_id;
    }

    public function setPrev($node) {
        $this->prev = $node;
    }

    public function setNext($node) {
        $this->next = $node;
    }

    public function __set($name, $value) {
        $method_name = 'set'.ucfirst($name);

        if(method_exists($this, $method_name)) {
            $this->$method_name($value);
        }
        else {
            $this->$name = $value;
        }
    }

    public function getData() {
        $data = $this->data;

        if(!is_object($data)) {
            return $data;
        }

        $data->prev = $this->prev === null ? null : $this->prev->id;
        $data->next = $this->next === null ? null : $this->next->id;

        return $data;
    }
}

class DoublyLinkedList implements \Iterator{
    private $_firstNode = null;
    private $_lastNode = null;
    private $_count = 0;

    private $_current = null;

    private $_tmpHashmap = null; // for importing

    public function isEmpty() {
        return $this->_count === 0;
    }

    public function insertFirst($item) {
        $newNode = new DoublyLinkedList_Node($item);

        if($this->isEmpty()) {
            $this->_lastNode = $this->_current = $newNode;
        }
        else {
            $this->_firstNode->prev = $newNode;
        }

        $newNode->next = $this->_firstNode;
        $this->_firstNode = $newNode;

        $this->_count++;
    }

    public function insertLast($item) {
        $newNode = new DoublyLinkedList_Node($item);

        if($this->isEmpty()) {
            $this->_firstNode = $this->_current = $newNode;
        }
        else {
            $this->_lastNode->next = $newNode;
        }

        $newNode->prev = $this->_lastNode;
        $this->_lastNode = $newNode;

        $this->_count++;
    }

    public function insertBefore($item, $id) {
        $current = $this->find($id);

        if(!$current) {
            return false;
        }

        $newNode = new DoublyLinkedList_Node($item);

        if($current == $this->_firstNode) {
            $newNode->prev = null;
            $this->_firstNode = $newNode;
        }
        else {
            $newNode->prev = $current->prev;
            $current->prev->next = $newNode;
        }

        $newNode->next = $current;
        $current->prev = $newNode;

        $this->_count++;

        return true;
    }

    public function insertAfter($item, $id) {
        $current = $this->find($id);

        if(!$current) {
            return false;
        }

        $newNode = new DoublyLinkedList_Node($item);

        if($current == $this->_lastNode) {
            $newNode->next = null;
            $this->_lastNode = $newNode;
        }
        else {
            $newNode->next = $current->next;
            $current->next->prev = $newNode;
        }

        $newNode->prev = $current;
        $current->next = $newNode;

        $this->_count++;

        return true;
    }

    public function delete($id) {
        $current = $this->find($id);
        $deleted = $current;

        if(!$current) {
            return false;
        }

        if($current == $this->_firstNode) {
            $this->_firstNode = $current->next;
        }
        else {
            $current->prev->next = $current->next;
        }

        if($current == $this->_lastNode) {
            $this->_lastNode = $current->prev;
        }
        else {
            $current->next->prev = $current->prev;
        }

        $this->_count--;

        $deleted->prev = null;
        $deleted->next = null;

        return $deleted;
    }

    public function find($id) {
        $current = $this->_firstNode;

        while($current->_id !== $id) {
            $current = $current->next;

            if($current == null) {
                return null;
            }
        }

        return $current;
    }

    public function findNth($n) {
        return $this->findNthFromNode($this->_firstNode, 0, $n);
    }

    public function findNthFromNode($node, $count, $n) {
        if($count == $n) {
            return $node;
        }

        if($node->next == null) {
            return null;
        }

        $count++;

        return $this->findNthFromNode($node->next, $count, $n);
    }

    public function first() {
        return $this->_firstNode;
    }

    public function last() {
        return $this->_lastNode;
    }

    public function current() {
        return $this->_current;
    }

    public function key() {
        return true;
    }

    public function next() {
        return $this->_current = $this->_current->next;
    }

    public function valid() {
        return $this->_current !== null;
    }

    public function cursorTo($id) {
        $node = $this->find($id);

        if($node !== null) {
            $this->_current = $node;
        }
    }

    public function rewind() {
        $this->_current = $this->_firstNode;
    }

    public function count() {
        return $this->_count;
    }

    public function importHashmap($hashmap, $head) {
        $this->_tmpHashmap = $hashmap;

        $this->insertIntoList($head);
    }

    protected function insertIntoList($id) {
        $current = (object) $this->_tmpHashmap[$id];
        $current->_id = $id; // tack on id as prop

        $this->insertLast($current);

        if($current->next != null) {
            $this->insertIntoList($current->next);
        }
    }

    public function getChangesAsHashmap() {
        $current = $this->_firstNode;

        $changes = array();

        while($current !== null) {
            $original = isset($this->_tmpHashmap[$current->id]) ? (object) $this->_tmpHashmap[$current->id] : false;

            if(!$original ||
                ($current->next == null && $original->next != null) ||
                ($current->prev == null && $original->prev != null) ||
                ($current->next !== null && $original->next !== $current->next->id) ||
                ($current->prev !== null && $original->prev !== $current->prev->id)
            ) {
                $data = $current->data;
                unset($data->_id);

                $changes[$current->id] = $data;
            }

            $current = $current->next;
        }

        return $changes;
    }

    public function getAllAsHashmap() {
        $results = array();

        $current = $this->_firstNode;

        while($current !== null) {
            $data = $current->data;
            unset($data->_id);

            $results[$current->id] = $data;

            $current = $current->next;
        }

        return $results;
    }
}

?>