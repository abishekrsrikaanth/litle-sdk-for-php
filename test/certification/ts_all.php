<?php
/*
* Copyright (c) 2011 Litle & Co.
*
* Permission is hereby granted, free of charge, to any person
* obtaining a copy of this software and associated documentation
* files (the "Software"), to deal in the Software without
* restriction, including without limitation the rights to use,
* copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the
* Software is furnished to do so, subject to the following
* conditions:
*
* The above copyright notice and this permission notice shall be
* included in all copies or substantial portions of the Software.
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND
* EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
* OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
* NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
* WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
* FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
* OTHER DEALINGS IN THE SOFTWARE.
*/

require_once realpath(dirname(__FILE__)) . '/../../lib/LitleOnline.php';

require realpath(dirname(__FILE__)) .  '/test_certification1_base-alpha.php';
require realpath(dirname(__FILE__)) .  '/test_certification1_base-beta.php';
require realpath(dirname(__FILE__)) .  '/test_certification2_authenhanced.php';
require realpath(dirname(__FILE__)) .  '/test_certification3_authReversal.php';
require realpath(dirname(__FILE__)) .  '/test_certification4_echeck.php';
require realpath(dirname(__FILE__)) .  '/test_certification5_token.php';

class CertificationTests
{
	public static function suite()
	{
		$suite = new PHPUnit_Framework_TestSuite('PHPUnit');
		$suite->addTestSuite('cert1_Test_alpha');
		$suite->addTestSuite('cert1_Test_beta');
		$suite->addTestSuite('cert2_Test');
		$suite->addTestSuite('cert3_Test');
		$suite->addTestSuite('cert4_Test');
		$suite->addTestSuite('cert5_Test');
		return $suite;
	}
}