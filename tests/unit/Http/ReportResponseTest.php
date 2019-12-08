<?php

namespace OCA\ConfigReport\Http;

use Test\TestCase;

class ReportResponseTest extends TestCase {
	protected function setUp(): void {
		parent::setUp();
	}

	public function testRender() {
		$expectedValue = "{}";
		$response = new ReportResponse(null, null, $expectedValue);
		$this->assertEquals($response->render(), $expectedValue);
	}
}
