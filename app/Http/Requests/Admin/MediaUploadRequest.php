<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MediaUploadRequest extends FormRequest
{
    private const ALLOWED_MIME_SIGNATURES = [
        // Images
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'image/gif' => ['GIF87a', 'GIF89a'],
        'image/webp' => ['RIFF'],
        'image/bmp' => ['BM'],
        'image/x-icon' => ["\x00\x00\x01\x00"],
        'image/vnd.microsoft.icon' => ["\x00\x00\x01\x00"],
        // Documents
        'application/pdf' => ['%PDF'],
        'application/zip' => ["PK\x03\x04", "PK\x05\x06"],
        'application/x-zip-compressed' => ["PK\x03\x04", "PK\x05\x06"],
        // Videos
        'video/mp4' => ["\x00\x00\x00\x18ftypmp4", "\x00\x00\x00\x1Cftypisom", "\x00\x00\x00"],
        'video/webm' => ["\x1A\x45\xDF\xA3"],
        'video/quicktime' => ["\x00\x00\x00"],
        'video/x-msvideo' => ['RIFF'],
        // Audio
        'audio/mpeg' => ["\xFF\xFB", "\xFF\xFA", "\xFF\xF3", 'ID3'],
        'audio/wav' => ['RIFF'],
        'audio/ogg' => ['OggS'],
        'audio/flac' => ['fLaC'],
    ];

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:10240', // 10MB
                'mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,mp4,webm,mp3,wav',
            ],
            'collection' => [
                'nullable',
                'string',
                'in:default,images,documents',
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->hasFile('file') && ! $this->verifyFileMagicBytes()) {
                    $validator->errors()->add(
                        'file',
                        'The file content does not match its extension. Please upload a valid file.'
                    );
                }
            },
        ];
    }

    private function verifyFileMagicBytes(): bool
    {
        $file = $this->file('file');
        if (! $file) {
            return false;
        }

        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Skip verification for SVG (text-based) and Office documents (complex format)
        $skipExtensions = ['svg', 'doc', 'docx'];
        if (in_array($extension, $skipExtensions)) {
            return true;
        }

        // Read first 32 bytes for magic byte check
        $handle = fopen($file->getRealPath(), 'rb');
        if (! $handle) {
            return false;
        }

        $header = fread($handle, 32);
        fclose($handle);

        if (! $header) {
            return false;
        }

        // Check if mime type has known signatures
        if (! isset(self::ALLOWED_MIME_SIGNATURES[$mimeType])) {
            // If we don't have a signature for this type, allow it (relies on mimes validation)
            return true;
        }

        // Verify magic bytes match
        foreach (self::ALLOWED_MIME_SIGNATURES[$mimeType] as $signature) {
            if (str_starts_with($header, $signature)) {
                return true;
            }
        }

        return false;
    }

    public function messages(): array
    {
        return [
            'file.max' => 'The file size must not exceed 10MB.',
            'file.mimes' => 'The file must be an image, document, or media file.',
        ];
    }
}
