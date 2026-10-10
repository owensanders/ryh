<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Repositories\DocumentRepositoryInterface;
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
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DocumentController extends Controller
{
    use ResolvesHouseholdTrait;
    use ResolvesUserTrait;

    public function __construct(private readonly DocumentRepositoryInterface $documents) {}

    public function index(Request $request, GetDocumentsScreenUseCase $getScreen, ?DocumentFolder $folder = null): Response
    {
        $household = $this->household($request);

        if ($folder !== null) {
            $this->assertFolderOwned($request, $folder);
        }

        return Inertia::render('Documents', $getScreen->execute($household, $folder));
    }

    public function storeFolder(DocumentFolderRequest $request, CreateDocumentFolderUseCase $create): RedirectResponse
    {
        $folder = $create->execute($this->household($request), (string) $request->validated('name'));

        return back()->with('toast', "“{$folder->name}” folder created");
    }

    public function destroyFolder(Request $request, DocumentFolder $folder, DeleteDocumentFolderUseCase $delete): RedirectResponse
    {
        $this->assertFolderOwned($request, $folder);

        $name = $folder->name;
        $delete->execute($folder);

        return redirect()->route('documents.index')->with('toast', "“{$name}” folder deleted");
    }

    public function store(DocumentUploadRequest $request, DocumentFolder $folder, UploadDocumentUseCase $upload): RedirectResponse
    {
        $this->assertFolderOwned($request, $folder);

        $document = $upload->execute($folder, $request->uploadedFile(), $this->user($request));

        return back()->with('toast', "“{$document->name}” uploaded");
    }

    public function move(DocumentMoveRequest $request, Document $document, MoveDocumentUseCase $move): RedirectResponse
    {
        $this->assertDocumentOwned($request, $document);

        $folder = $this->documents->findFolder((int) $request->validated('folder_id'));
        abort_if($folder === null, 404);
        $this->assertFolderOwned($request, $folder);

        $moved = $move->execute($document, $folder);

        return back()->with('toast', "“{$moved->name}” moved to {$folder->name}");
    }

    public function download(Request $request, Document $document): SymfonyResponse
    {
        $this->assertDocumentOwned($request, $document);

        return Storage::disk(config('documents.disk'))->download($document->path, $document->name);
    }

    public function destroy(Request $request, Document $document, DeleteDocumentUseCase $delete): RedirectResponse
    {
        $this->assertDocumentOwned($request, $document);

        $name = $document->name;
        $delete->execute($document);

        return back()->with('toast', "“{$name}” deleted");
    }

    private function assertFolderOwned(Request $request, DocumentFolder $folder): void
    {
        abort_if($folder->household_id !== $this->household($request)->id, 404);
    }

    private function assertDocumentOwned(Request $request, Document $document): void
    {
        abort_if($document->household_id !== $this->household($request)->id, 404);
    }
}
