<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\DocumentFolderRequest;
use App\Http\Requests\DocumentMoveRequest;
use App\Http\Requests\DocumentUploadRequest;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\UseCases\Documents\CreateDocumentFolderUseCase;
use App\UseCases\Documents\DeleteDocumentFolderUseCase;
use App\UseCases\Documents\DeleteDocumentUseCase;
use App\UseCases\Documents\GetDocumentsScreenUseCase;
use App\UseCases\Documents\MoveDocumentUseCase;
use App\UseCases\Documents\UploadDocumentUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DocumentController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    /** How long an S3 download link works for — long enough to start the download, short enough not to be worth sharing. */
    private const int DOWNLOAD_LINK_MINUTES = 5;

    public function index(Request $request, GetDocumentsScreenUseCase $getScreen, ?DocumentFolder $folder = null): Response
    {
        $household = $this->household($request);

        return Inertia::render('Documents', $getScreen->execute($household, $folder));
    }

    public function storeFolder(DocumentFolderRequest $request, CreateDocumentFolderUseCase $create): RedirectResponse
    {
        $folder = $create->execute($this->household($request), (string) $request->validated('name'));

        return back()->with('toast', "“{$folder->name}” folder created");
    }

    public function destroyFolder(DocumentFolder $folder, DeleteDocumentFolderUseCase $delete): RedirectResponse
    {
        $name = $folder->name;
        $delete->execute($folder);

        return redirect()->route('documents.index')->with('toast', "“{$name}” folder deleted");
    }

    public function store(DocumentUploadRequest $request, DocumentFolder $folder, UploadDocumentUseCase $upload): RedirectResponse
    {
        $document = $upload->execute($folder, $request->uploadedFile(), $this->user($request));

        return back()->with('toast', "“{$document->name}” uploaded");
    }

    public function move(DocumentMoveRequest $request, Document $document, MoveDocumentUseCase $move): RedirectResponse
    {
        $moved = $move->execute($document, (int) $request->validated('folder_id'));

        return back()->with('toast', "“{$moved->document->name}” moved to {$moved->folderName}");
    }

    public function download(Document $document): SymfonyResponse
    {
        $disk = (string) config('documents.disk');

        // On S3 the browser fetches the file straight from the bucket through a
        // short-lived link, rather than a PHP worker relaying every byte.
        if (config("filesystems.disks.{$disk}.driver") === 's3') {
            return redirect()->away(Storage::disk($disk)->temporaryUrl(
                $document->path,
                now()->addMinutes(self::DOWNLOAD_LINK_MINUTES),
                ['ResponseContentDisposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $document->name, str_replace(['%', '/', '\\'], '', Str::ascii($document->name)))],
            ));
        }

        return Storage::disk($disk)->download($document->path, $document->name);
    }

    public function destroy(Document $document, DeleteDocumentUseCase $delete): RedirectResponse
    {
        $name = $document->name;
        $delete->execute($document);

        return back()->with('toast', "“{$name}” deleted");
    }
}
