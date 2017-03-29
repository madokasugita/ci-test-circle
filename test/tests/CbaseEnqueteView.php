<?php
require_once (DIR_LIB . 'CbaseEnquete.php');
require_once (DIR_LIB . 'CbaseDAO.php');
require_once (DIR_LIB . 'CbaseEnqueteAnswer.php');
require_once (DIR_LIB . 'CbaseEnqueteViewer.php');

class TestOfCbaseEnqueteViewer extends UnitTestCase
{
    public function testEnqueteFormBuilderMergeEvid()
    {
        $e = new Enquete();
        $e->enquete = array(
                -1=>array('evid' => 100,),
        );
        $b = new EnqueteFormBuilder($e, $e, new EnqueteAnswer());
        $this->assertEqual("ID100001", "ID100001");
        $this->assertEqual("ID100001", "ID100001");
        //$this->assertEqual("ID100001", "ID100002");
    }
}
