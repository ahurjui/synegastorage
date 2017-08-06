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
    public function index()
    {
        $files = File::paginate(10);

        if (!$files) {
            throw new HttpException(400, "Invalid data");
        }

        return response()->json(
            $files,
            200
        );
    }

    public function show($id)
    {
        if (!$id) {
            throw new HttpException(400, "Invalid id");
        }

        $file = File::find($id);

        return response()->json([
            $file,
        ], 200);

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
            'file' => 'bail|required'
        ]);

        $currentUserId = Auth::user()->id;

        $file = new File();
        $file->user_id = $currentUserId;

        if($request->hasFile('file')) { //check if file parameter exists
            $uploadedFile = $request->file('file');
            $applicationInsideName = md5($file->name. time()).'.'.$uploadedFile->getClientOriginalExtension();

            $file->name = $uploadedFile->getClientOriginalName();
            $file->application_inside_name = $applicationInsideName;
            $file->disk_location = 'uploads/'.$currentUserId;
        } else {
            return response()->json([
                'message' => 'Invalid data, file parameter not found!',
            ], 400);
        }

        if (isset($uploadedFile) && $uploadedFile->move($file->disk_location, $applicationInsideName)) {
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
            'file' => 'bail|required'
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
            $applicationInsideName = md5($file->name. time()).'.'.$uploadedFile->getClientOriginalExtension();
            $oldFilePath = public_path().'/'.$file->disk_location.'/'.$file->application_inside_name;
            $oldOriginalFileName = $file->name;

            $file->name = $uploadedFile->getClientOriginalName();
            $file->application_inside_name = $applicationInsideName;
            $file->disk_location = 'uploads/'.$currentUserId;
        } else {
            return response()->json([
                'message' => 'Invalid data, file parameter not found!',
            ], 400);
        }

        $upload = $uploadedFile->move($file->disk_location, $applicationInsideName);
        if (isset($uploadedFile) && $upload) {
            if ($file->save()) {
                $path = public_path().'/recycle/'.$currentUserId.'/';

                if (!FileSystem::isDirectory($path)) {
                    FileSystem::makeDirectory($path, 0777, true, true);
                }

                FileSystem::move($oldFilePath, $path.$oldOriginalFileName);
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

        $oldFilePath = public_path().'/'.$file[0]->disk_location.'/'.$file[0]->application_inside_name;
        $oldOriginalFileName = $file[0]->name;
        $path = public_path().'/recycle/'.Auth::user()->id.'/';


        if (!FileSystem::isDirectory($path)) {
            FileSystem::makeDirectory($path, 0777, true, true);
        }

        FileSystem::move($oldFilePath, $path.$oldOriginalFileName);

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
}