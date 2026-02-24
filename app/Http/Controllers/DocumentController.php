<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Trip $trip)
    {
        $this->authorize('viewAny', [Document::class, $trip]);

        return response()->json($trip->documents()->with('user')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Trip $trip)
    {
        $this->authorize('create', [Document::class, $trip]);

        $request->validate([
            'document' => ['required', 'file', 'max:10240'], // Max 10MB
            'description' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:50'],
        ]);

        if (! $request->hasFile('document')) {
            throw ValidationException::withMessages([
                'document' => 'No document file provided.',
            ]);
        }

        $file = $request->file('document');
        $filePath = $file->store('documents', 'public'); // Store in storage/app/public/documents

        $document = $trip->documents()->create([
            'user_id' => Auth::id(),
            'file_path' => $filePath,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'description' => $request->input('description'),
            'category' => $request->input('category'),
        ]);

        return response()->json($document->load('user'), 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Document $document)
    {
        $this->authorize('delete', $document);

        // Delete the file from storage
        Storage::disk('public')->delete($document->file_path);

        // Delete the document record from the database
        $document->delete();

        return response()->noContent();
    }
}
