<?php

namespace App\Http\Controllers;

use App\Services\Vectara;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
  public function store(Request $request)
  {
    try {
      // If we're not testing
      if (!app()->runningUnitTests()) {
        // Validate incoming request
        $request->validate([
          'file' => 'required|mimetypes:application/json,application/pdf,text/markdown,text/plain', // |max:1000240
        ]);
      }

      // Retrieve the uploaded file
      $file = $request->file('file');

      // Store the file
      $path = Storage::putFile('uploads', $file);


      // Create a Vectara corpus
      $vectara = new Vectara();

      // if (!app()->runningUnitTests()) {
        // Create an UploadedFile from there
        $file = new \Illuminate\Http\UploadedFile(
          storage_path('app/' . $path),
          $file->getClientOriginalName(),
          $file->getMimeType(),
          null,
          true
        );

        // TODO: Send file to Vectara, save something in our local database

        $res = $vectara->upload(4, $file);
      // }

      return Redirect::route('start')
        ->with('message', 'File uploaded.')
        ->with('filename', $file->getClientOriginalName());
    } catch (\Exception $e) {
      // dd($e);
      return Redirect::route('start')->with('error', 'Error uploading file.');
    }
  }
}
