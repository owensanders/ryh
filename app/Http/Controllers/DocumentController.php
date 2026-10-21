<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Documents\CreateDocumentFolderAction;
use App\Actions\Documents\DeleteDocumentAction;
use App\Actions\Documents\DeleteDocumentFolderAction;
use App\Actions\Documents\DownloadDocumentAction;
use App\Actions\Documents\MoveDocumentAction;
use App\Actions\Documents\UploadDocumentAction;
use App\Http\Requests\DocumentFolderRequest;
use App\Http\Requests\DocumentMoveRequest;
use App\Http\Requests\DocumentUploadRequest;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Traits\ResolvesHouseholdTrait;
use App\Traits\ResolvesUserTrait;
use App\ViewModels\Documents\DocumentsViewModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DocumentController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function index(Request $request, DocumentsViewModel $getScreen, ?DocumentFolder $folder = null): Response
    {
        $household = $this->household($request);

        return Inertia::render('Documents', $getScreen->toData($household, $folder));
    }

    public function storeFolder(DocumentFolderRequest $request, CreateDocumentFolderAction $create): RedirectResponse
    {
        $folder = $create->handle($this->household($request), (string) $request->validated('name'));

        return back()->with('toast', "“{$folder->name}” folder created");
    }

    public function destroyFolder(DocumentFolder $folder, DeleteDocumentFolderAction $delete): RedirectResponse
    {
        $name = $folder->name;
        $delete->handle($folder);

        return redirect()->route('documents.index')->with('toast', "“{$name}” folder deleted");
    }

    public function store(DocumentUploadRequest $request, DocumentFolder $folder, UploadDocumentAction $upload): RedirectResponse
    {
        $document = $upload->handle($folder, $request->uploadedFile(), $this->user($request));

        return back()->with('toast', "“{$document->name}” uploaded");
    }

    public function move(DocumentMoveRequest $request, Document $document, MoveDocumentAction $move): RedirectResponse
    {
        $folder = $move->handle($document, (int) $request->validated('folder_id'));

        return back()->with('toast', "“{$document->name}” moved to {$folder->name}");
    }

    public function download(Document $document, DownloadDocumentAction $download): SymfonyResponse
    {
        return $download->handle($document);
    }

    public function destroy(Document $document, DeleteDocumentAction $delete): RedirectResponse
    {
        $name = $document->name;
        $delete->handle($document);

        return back()->with('toast', "“{$name}” deleted");
    }
}
