<?php
// =begin
// Copyright (c) 2011 Litle & Co.

// Permission is hereby granted, free of charge, to any person
// obtaining a copy of this software and associated documentation
// files (the "Software"), to deal in the Software without
// restriction, including without limitation the rights to use,
// copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the
// Software is furnished to do so, subject to the following
// conditions:

// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Software.

// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
// OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
// WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
// FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
// OTHER DEALINGS IN THE SOFTWARE.
// =end
require_once("../../simpletest/autorun.php");
require_once('../../simpletest/unit_tester.php');
require_once('../../simpletest/mock_objects.php');

require_once realpath(dirname(__FILE__)) . '/../../lib/LitleOnline.php';

class checker_UnitTest extends UnitTestCase

{
	function test_required()
	{
		$hash1 = null;
		$hash2 = Checker::required_field($hash2);
		$this->assertEqual($hash2,'REQUIRED');
	}
	
	function test_choice()
	{
		$hash1= null;
		$hash2= array('21321','214323');
		$hash3 = array('143543','78987');
		$hash4 = array($hash1,$hash2,$hash3);
		$this->expectException(new Exception("Entered an Invalid Amount of Choices for a Field, please only fill out one Choice!!!!"));
		Checker::choice($hash4);
	}
}
?>