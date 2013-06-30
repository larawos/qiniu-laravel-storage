<?php

require("bootstrap.php");

class RsfTest extends PHPUnit_Framework_TestCase
{
	public $rsf;
	public $bucket = BUCKET_NAME;

	public function setUp()
	{
		$this->rsf = new Qiniu_RSF_Client(null);
	}

	public function testRsf()
	{
		list($ret, $err) = $this->rsf->ListPrefix($this->bucket, null, null, null);
		$this->assertEquals($err, 'EOF');
		$this->assertArrayHasKey('hash', $ret['items'][0]);

		list($ret, $err) = $this->rsf->ListPrefix($this->bucket, null, null, 1);
		$this->assertNull($err);
		$this->assertArrayHasKey('marker', $ret);

		list($ret, $err) = $this->rsf->ListPrefix($this->bucket, "file", $ret['marker'], 2);
		$this->assertNull($err);
	}

}
