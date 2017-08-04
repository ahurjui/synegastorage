<?php

namespace App\Http\Controllers;

use Illuminate\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\File;

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

    public function store(Request $request)
    {
        $file = new File;
        $file->name = '';
        $file->disk_location = '';
        $file->application_inside_name = '';

        if ($file->save()) {
            return $file;
        }

        throw new HttpException(400, "Invalid data");
    }

    public function update(Request $request, $id)
    {
        if (!$id) {
            throw new HttpException(400, "Invalid id");
        }

        $file = File::find($id);
        $file->name = '';
        $file->disk_location = '';
        $file->application_inside_name = '';

        if ($file->save()) {
            return $file;
        }

        throw new HttpException(400, "Invalid data");
    }

    public function destroy($id)
    {
        if (!$id) {
            throw new HttpException(400, "Invalid id");
        }

        $file = File::find($id);
        $file->delete();

        return response()->json([
            'message' => 'file deleted',
        ], 200);
    }
}