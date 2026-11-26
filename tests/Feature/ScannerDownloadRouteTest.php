<?php

namespace Tests\Feature;

use Tests\TestCase;

class ScannerDownloadRouteTest extends TestCase
{
    public function test_scanner_download_route_returns_successful_download(): void
    {
        $response = $this->get(route('scanner.download'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains((string) $response->headers->get('content-disposition'), 'HospitalScanner_Setup.zip')
            || str_contains((string) $response->headers->get('content-disposition'), 'HospitalScanner.exe')
        );
    }
}
