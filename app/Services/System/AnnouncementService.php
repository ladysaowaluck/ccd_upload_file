<?php

namespace App\Services\System;

use Illuminate\Support\Facades\File;

class AnnouncementService
{
    private string $filePath;

    public function __construct()
    {
        $this->filePath = storage_path('app/announcement.json');
    }

    /**
     * Retrieve the current system announcement data.
     */
    public function get(): array
    {
        if (File::exists($this->filePath)) {
            $content = File::get($this->filePath);
            $data = json_decode($content, true);
            if (is_array($data)) {
                return array_merge($this->defaultAnnouncement(), $data);
            }
        }

        return $this->defaultAnnouncement();
    }

    /**
     * Update the system announcement.
     */
    public function update(array $data, ?string $author = null): array
    {
        $announcement = [
            'title'      => trim((string) ($data['title'] ?? 'System Announcement')),
            'message'    => trim((string) ($data['message'] ?? '')),
            'type'       => in_array($data['type'] ?? '', ['info', 'warning', 'success', 'danger'], true) ? $data['type'] : 'info',
            'is_active'  => !empty($data['is_active']),
            'updated_by' => $author ?: 'Administrator',
            'updated_at' => date('Y-m-d H:i'),
        ];

        $directory = dirname($this->filePath);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($this->filePath, json_encode($announcement, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $announcement;
    }

    /**
     * Fallback default announcement.
     */
    private function defaultAnnouncement(): array
    {
        return [
            'title'      => 'Welcome to Customs Clearance System (CCD)',
            'message'    => 'Please ensure all Requisition forms and HS evidence files are attached before final submission.',
            'type'       => 'info',
            'is_active'  => true,
            'updated_by' => 'System Admin',
            'updated_at' => date('Y-m-d H:i'),
        ];
    }
}
