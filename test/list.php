<?php namespace SAS\Test;

use SAS\DoublyLinkedList as DoublyLinkedList;

require dirname(dirname(__FILE__)) . '/vendor/autoload.php';
require dirname(dirname(__FILE__)) . '/list.php';

class ListTest extends \PHPUnit_Framework_TestCase {
    public function testList() {
        $list = new DoublyLinkedList();

        $this->assertTrue($list->isEmpty());

        $list->insertLast(1);

        $this->assertFalse($list->isEmpty());
        $this->assertSame($list->count(), 1);

        $retrieved = $list->current();
        $this->assertNull($retrieved->prev);
        $this->assertNull($retrieved->next);

        $this->assertSame($retrieved->data, 1);

        $list->insertLast(2);

        $this->assertSame($list->count(), 2);

        $retrieved = $list->next();
        $this->assertSame($retrieved->data, 2);
        $this->assertSame($retrieved->prev->next->id, 2);
        $this->assertNull($retrieved->next);

        $retrieved = $list->current();
        $this->assertSame($retrieved->data, 2);

        $list->rewind();
        $retrieved = $list->current();
        $this->assertSame($retrieved->data, 1);

        $list->insertLast(3);
        $list->insertLast(4);

        $list->insertBefore(2.5, 3);

        $list->next();
        $retrieved = $list->next();

        $this->assertSame($retrieved->data, 2.5);
        $this->assertSame($retrieved->next->prev->id, 2.5);
        $this->assertSame($retrieved->next->id, 3);
        $this->assertSame($retrieved->prev->next->id, 2.5);
        $this->assertSame($retrieved->prev->id, 2);

        $retrieved = $list->next();
        $this->assertSame($retrieved->data, 3);

        $retrieved = $list->find(4);
        $this->assertSame($retrieved->data, 4);

        $list->insertAfter(4.3, 4);
        $retrieved = $list->find(4.3);
        $this->assertSame($retrieved->data, 4.3);
        $this->assertSame($retrieved->prev->next->id, 4.3);
        $this->assertNull($retrieved->next);

        // test cursorTo
        $list->cursorTo(4.3);
        $retrieved = $list->current();
        $this->assertSame($retrieved->data, 4.3);

        // test that was added after
        $retrieved = $list->find($retrieved->prev->id);
        $this->assertSame($retrieved->data, 4);

        $this->assertSame($list->count(), 6);

        $deleted = $list->delete(4);
        $this->assertSame($deleted->id, 4);
        $retrieved = $list->find(4);
        $this->assertNull($retrieved);
        $this->assertSame($list->count(), 5);

        return $list;
    }

    /**
     * @depends testList
     */
    function testMoveItemToNewPosition($list) {
        $list->delete(3);
        $list->insertFirst(3);
        $list->rewind();
        $retrieved = $list->current();

        $this->assertSame($list->count(), 5);
        $this->assertSame($retrieved->data, 3);
    }

    function testImportAndAdd() {
        $list = new DoublyLinkedList();

        $hm = array(
            'head' => 3,
            'items' => array(
                '1' => array(
                    'name' => 'A&W Root Beer',
                    'prev' => 4,
                    'next' => 2,
                    'test_order' => 2
                ),
                '2' => array(
                    'name' => 'Sprite',
                    'prev' => 1,
                    'next' => 5,
                    'test_order' => 3
                ),
                '3' => array(
                    'name' => 'Dr. Pepper',
                    'prev' => null,
                    'next' => 4,
                    'test_order' => 0
                ),
                '4' => array(
                    'name' => 'Vernors',
                    'prev' => 3,
                    'next' => 1,
                    'test_order' => 1
                ),
                '5' => array(
                    'name' => 'Pepsi',
                    'prev' => 2,
                    'next' => null,
                    'test_order' => 4
                )
            )
        );

        $list->importHashmap($hm['items'], $hm['head']);

        $this->assertSame(count($hm['items']), $list->count());

        $count = 0;

        foreach($list as $list_item) {
            $this->assertSame($list_item->data->test_order, $count);
            $count++;
        }

        // make sure we iterated through all
        $this->assertSame($count, $list->count());

        $list->rewind();
        $first = $list->current();

        $this->assertSame($first->id, $hm['head']);

        return $list;
    }

    /**
     * @depends testImportAndAdd
     */
    function testAppendItemToImportedList($list) {
        $original_count = $list->count();

        $new_drink = (object) array(
            'id' => 6,
            'name' => 'Fresca'
        );

        $list->insertLast($new_drink);

        $this->assertSame($original_count + 1, $list->count());

        $last = $list->last();
        $this->assertSame($last->id, $new_drink->id);
        $this->assertNull($last->next);

        return $list;
    }

    /**
     * @depends testAppendItemToImportedList
     */
    function testMoveItemWithinList($list) {
        // move Pepsi to second spot in list
        $item = $list->delete(5);

        // deleted item should have prev and next nulled out
        $this->assertNull($item->next);
        $this->assertNull($item->prev);
        $this->assertNull($item->data->next);
        $this->assertNull($item->data->prev);

        $this->assertSame($item->id, 5);
        $list->insertBefore($item->data, 4);

        $inserted = $list->find(5);

        $this->assertNotNull($inserted->next);
        $this->assertNotNull($inserted->prev);
        $this->assertNotNull($inserted->data->next);
        $this->assertNotNull($inserted->data->prev);

        $this->assertSame($inserted->prev->id, 3);
        $this->assertSame($inserted->next->id, 4);

        $expected_order = array(3, 5, 4, 1, 2, 6);

        $list->rewind();

        $count = 0;

        foreach($list as $list_item) {
            $this->assertSame($list_item->id, $expected_order[$count]);
            $count++;
        }

        return $list;
    }

    /**
     * @depends testMoveItemWithinList
     */
    function testFindNthItemFromList($list) {
        $result = $list->findNth(4);
        $this->assertSame($result->id, 2);

        return $list;
    }

    /**
     * @depends testFindNthItemFromList
     */
    function testGetChangesForList($list) {
        $changes = $list->getChangesAsHashmap();

        $this->assertSame(count($changes), 5);

        $this->assertSame($changes[3]->next, 5);
        $this->assertSame($changes[5]->next, 4);
        $this->assertSame($changes[5]->prev, 3);
        $this->assertSame($changes[4]->prev, 5);
        $this->assertSame($changes[4]->next, 1);
        $this->assertSame($changes[2]->next, 6);
        $this->assertSame($changes[2]->prev, 1);
        $this->assertSame($changes[6]->prev, 2);
        $this->assertSame($changes[6]->next, null);

        return $list;
    }

    /**
     * @depends testGetChangesForList
     */
    function testGetChangesWhenItemRemovedFromList($list) {
        $list->rewind();
        $to_remove = $list->next();

        $deleted = $list->delete($to_remove->id);

        $changes = $list->getChangesAsHashmap();

        $this->assertSame(count($changes), 2);

        return $list;
    }

    /**
     * @depends testGetChangesWhenItemRemovedFromList
     */
    function testGetAllForList($list) {
        $all = $list->getAllAsHashmap();

        $this->assertSame(count($all), $list->count());
    }
}

?>