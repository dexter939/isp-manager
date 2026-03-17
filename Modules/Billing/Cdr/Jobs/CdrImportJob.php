<?php
namespace Modules\Billing\Cdr\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\Cdr\Models\CdrImportFile;
use Modules\Billing\Cdr\Services\CdrImporter;
class CdrImportJob implements ShouldQueue, ShouldBeUnique {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct(public readonly int $importFileId) {}
    public function handle(CdrImporter $importer): void {
        $file    = CdrImportFile::findOrFail($this->importFileId);
        $content = Storage::disk('minio')->get("cdr-imports/{$file->filename}");
        if (!$content) { $file->update(['status' => 'failed', 'error_message' => 'File not found in storage']); return; }
        $importer->import($content, $file->filename, $file->format);
    }
}
