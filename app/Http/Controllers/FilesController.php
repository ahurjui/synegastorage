<?php

namespace App\Http\Controllers;

use Illuminate\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Mockery\CountValidator\Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\File;
use Auth;
use Illuminate\Support\Facades\File as FileSystem;

class FilesController extends Controller
{
    /**
     * Get a list of all files
     * @return mixed
     */
    public function index()
    {
        $files = File::where([['status', '=', File::STATUS_ACTIVE]])->get();

        if (!$files) {
            return response()->json([
                'message' => 'Invalid data!',
            ], 400);
        }

        return response()->json(
            $files,
            200
        );
    }

    /**
     * Show details about one file
     *
     * @param $id
     * @return mixed
     */
    public function show(Request $request, $id)
    {
        if (!$id) {
            return response()->json([
                'message' => 'Invalid id!',
            ], 400);
        }

        $file = File::where('id', $id)->where('status', File::STATUS_ACTIVE)->limit(1)->get();
        if (!$file->all()) {
            return response()->json([
                'message' => 'Invalid id, file record not found!',
            ], 400);
        }

        return response()->json(
            $file[0]
        , 200);

    }


    /**
     * Upload a file and save information into the database about it
     *
     * @param Request $request
     * @return File/json with errors
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'file' => 'required',
            'file_path' => 'required',
            'client_id' => 'required'
        ]);

        $currentUserId = Auth::user()->id;

        $file = new File();
        $file->user_id = $currentUserId;
        $file->client_id = $request->get('client_id');
        $file->file_path = $request->get('file_path');

        if($request->hasFile('file')) { //check if file parameter exists
            $uploadedFile = $request->file('file');
            $file->name = $uploadedFile->getClientOriginalName();
        } else {
            return response()->json([
                'message' => 'Invalid data, file parameter not found!',
            ], 400);
        }

        if (isset($uploadedFile) && $uploadedFile->move('uploads/'.$file->client_id.'/'.$file->file_path, $uploadedFile->getClientOriginalName())) {
            if ($file->save()) {
                return $file;
            }
        } else {
            return response()->json([
                'message' => 'Invalid data, file could not be uploaded!',
            ], 400);
        }

        return response()->json([
            'message' => 'Invalid data!',
        ], 400);
    }

    /**
     * Update a record in database with the new file information
     *
     * @param Request $request
     * @param $id
     * @return mixed|static
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'file' => 'required',
            'file_path' => 'required',
            'client_id' => 'required'
        ]);

        $currentUserId = Auth::user()->id;

        $file = File::where('id', $id)->where('status', File::STATUS_ACTIVE)->limit(1)->get();
        if (!$file->all()) {
            return response()->json([
                'message' => 'Invalid id, file record not found!',
            ], 400);
        }

        $file = $file[0];
        $file->user_id = $currentUserId;

        if($request->hasFile('file')) { //check if file parameter exists
            $uploadedFile = $request->file('file');
            $oldFile = 'uploads/'.$file->client_id.'/'.$file->file_path.'/'.$file->name;

            //move the previous file to the recycle directory
            $recyclePath = public_path().'/recycle/'.$file->file_path.'/';
            if (!FileSystem::isDirectory($recyclePath)) {
                FileSystem::makeDirectory($recyclePath, 0777, true, true);
            }
            FileSystem::move($oldFile, $recyclePath.$file->name);

            $file->client_id = $request->get('client_id');
            $file->file_path = $request->get('file_path');
            $file->name = $uploadedFile->getClientOriginalName();
        } else {
            return response()->json([
                'message' => 'Invalid data, file parameter not found!',
            ], 400);
        }

        $upload = $uploadedFile->move('uploads/'.$file->client_id.'/'.$file->file_path, $file->name);
        if (isset($uploadedFile) && $upload) {
            if ($file->save()) {
                return $file;
            }
        } else {
            return response()->json([
                'message' => 'Invalid data, file could not be uploaded!',
            ], 400);
        }

        return response()->json([
            'message' => 'Invalid data!',
        ], 400);
    }

    /**
     * Delete one file. The method marks it as deleted and moves it to another directory called recycle
     * @param $id
     * @return mixed
     */
    public function destroy($id)
    {
        if (!$id) {
            throw new HttpException(400, "Invalid id");
        }

        $file = File::where('id', $id)->where('status', File::STATUS_ACTIVE)->limit(1)->get();
        if (!$file->all()) {
            return response()->json([
                'message' => 'Invalid id, file record not found!',
            ], 400);
        }

        $oldFilePath = public_path().'/uploads/'.$file[0]->client_id.'/'.$file[0]->file_path.'/'.$file[0]->name;
        $recyclePath = public_path().'/recycle/'.$file[0]->client_id.'/'.$file[0]->file_path.'/';


        if (!FileSystem::isDirectory($recyclePath)) {
            FileSystem::makeDirectory($recyclePath, 0777, true, true);
        }

        FileSystem::move($oldFilePath, $recyclePath.$file[0]->name);

        $file[0]->status = File::STATUS_ARCHIVED;

        if ($file[0]->save()) {
            return response()->json([
                'message' => 'file deleted',
            ], 200);
        } else {
            return response()->json([
                'message' => 'An error as occurred when deleting the file!',
            ], 400);
        }
    }

    /**
     * Empty recycle directory
     */
    public function recycle()
    {
        $directory = public_path().'/recycle/';
        $success = FileSystem::cleanDirectory($directory);

        if ($success) {
            return response()->json([
                'message' => 'The recycle bin was deleted!',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Something went wrong!',
            ], 400);
        }
    }

    /**
     * Download file
     *
     * @param int $id - id of the file to be downloaded
     * @param string $name - the name of the file
     */
    public function download($id, $name)
    {
        if (!$id) {
            return response()->json([
                'message' => 'Invalid data, record not found!',
            ], 400);
        }

        $file = File::where('id', $id)->where('status', File::STATUS_ACTIVE)->limit(1)->get();
        if (!$file->all()) {
            return response()->json([
                'message' => 'Invalid id, file record not found!',
            ], 400);
        }

        $file = $file[0];
        if (!$name) {
            $name = $file->name;
        }

        $path = public_path().'/uploads/'.$file->client_id.'/'.$file->file_path.'/'.$file->name;

        return response()->download($path, $name);
    }
}