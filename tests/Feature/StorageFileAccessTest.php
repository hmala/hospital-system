<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class StorageFileAccessTest extends TestCase
{
    public function test_can_access_public_storage_files_without_403()
    {
        Storage::disk('public')->put('radiology_images/test_sample.jpg', 'fake image content');

        $response = $this->get('/storage/radiology_images/test_sample.jpg');

        $response->assertStatus(200);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class, $response->baseResponse);
        $this->assertStringContainsString('fake image content', file_get_contents($response->baseResponse->getFile()->getPathname()));

        Storage::disk('public')->delete('radiology_images/test_sample.jpg');
    }

    public function test_storage_path_traversal_is_blocked()
    {
        $response = $this->get('/storage/../.env');
        $response->assertStatus(404);
    }

    public function test_non_existent_file_returns_404()
    {
        $response = $this->get('/storage/radiology_images/non_existent_file_12345.jpg');
        $response->assertStatus(404);
    }
}
