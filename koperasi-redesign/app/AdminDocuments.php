<?php

namespace App;

use App\Models\Dokumen;
use Illuminate\Http\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class AdminDocuments
{
    private function authorize(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    public function save(array $input, ?Dokumen $record = null): Dokumen
    {
        $this->authorize();
        $data = Validator::make($input, [
            'judul' => ['required', 'string', 'max:255', Rule::unique('dokumens', 'judul')->where('tahun', $input['tahun'] ?? null)->ignore($record?->id)],
            'tahun' => ['required', 'integer', 'min:1', 'max:65535'], 'file_path' => [$record === null ? 'required' : 'nullable', 'string', 'max:2048'], 'published' => ['required', 'boolean'],
        ])->validate();
        $path = $data['file_path'] ?? null;
        if ($path !== null) {
            $this->validateFile($path);
        }
        $old = $record?->file_path;
        $document = DB::transaction(function () use ($record, $data): Dokumen {
            if ($record === null) {
                return Dokumen::create($data);
            }$record->update($data);

            return $record->refresh();
        });
        if ($old !== null && $old !== $document->file_path) {
            $this->removeUnusedFile($old);
        }

        return $document;
    }

    public function publish(Dokumen $record, bool $published): bool
    {
        $this->authorize();
        $record->update(['published' => $published]);

        return $record->published;
    }

    public function delete(Dokumen $record): bool
    {
        $this->authorize();
        $path = $record->file_path;
        $deleted = DB::transaction(fn (): bool => (bool) $record->delete());
        if ($deleted && $path !== null) {
            $this->removeUnusedFile($path);
        }

        return $deleted;
    }

    private function removeUnusedFile(string $path): void
    {
        if (! Dokumen::where('file_path', $path)->exists() && app(MemberOverview::class)->privateDocumentPath($path) !== null) {
            Storage::disk('local')->delete($path);
        }
    }

    private function validateFile(string $path): void
    {
        $safe = app(MemberOverview::class)->privateDocumentPath($path);
        if ($safe === null || ! str_starts_with($safe, 'documents/') || ! is_file(Storage::disk('local')->path($safe))) {
            throw ValidationException::withMessages(['file_path' => 'Berkas private tidak tersedia.']);
        }
        $file = new File(Storage::disk('local')->path($safe));
        Validator::make(['file_path' => $file], ['file_path' => ['required', 'file', 'max:10240']], ['file_path.max' => 'Berkas maksimal 10 MB.'])->validate();
        $extension = strtolower(pathinfo($safe, PATHINFO_EXTENSION));
        $valid = false;
        if ($extension === 'pdf') {
            $valid = $file->getMimeType() === 'application/pdf';
        } elseif (in_array($extension, ['docx', 'xlsx'], true)) {
            $zip = new ZipArchive;
            if ($zip->open($file->getPathname()) === true) {
                try {
                    $valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName($extension === 'docx' ? 'word/document.xml' : 'xl/workbook.xml') !== false;
                } finally {
                    $zip->close();
                }
            }
        }
        if (! $valid) {
            throw ValidationException::withMessages(['file_path' => 'Berkas harus PDF, DOCX, atau XLSX yang valid.']);
        }
    }

    public function metadata(Dokumen $record): array
    {
        $path = app(MemberOverview::class)->privateDocumentPath($record->file_path);

        return ['type' => $path === null ? '-' : strtoupper(pathinfo($path, PATHINFO_EXTENSION)), 'size' => $path === null ? '-' : number_format(Storage::disk('local')->size($path) / 1024, 1, ',', '.').' KB'];
    }
}
