<?php

namespace Modules\TagLineCount\Http\Controllers;

use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\TagLineCount\Entities\TagLine;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Validator;

class TagLineController extends Controller
{
    use FileUploadTrait;
    public function index()
    {
        try 
        {
            $list = TagLine::all();
            // Ensure icon is full URL in all taglines
            $list = $list->map(function($item) {
                $arr = $item->toArray();
                if (!empty($arr['icon']) && !str_starts_with($arr['icon'], 'http')) {
                    $arr['icon'] = config('constants.file_url') . $arr['icon'];
                }
                return $arr;
            });
            if($list->isEmpty()) 
            {
                return response()->json([
                    'success' => true,
                    'message' => 'No taglines found.',
                    'data' => []
                ], 200);
            }
            return response()->json(['success' => true, 'data' => $list], 200);
        }
        catch(Exception $e)
        {
            return response()->json(['success' => false, 'message' => 'Failed to fetch taglines.'], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
            'percentage' => 'required|string',
            'description' => 'required|string',
            'status' => 'nullable|in:0,1',
            'unit'=> 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        try 
        {
            $validated = $validator->validated();

            if ($request->hasFile('icon')) {
                $image = $request->file('icon');
                $imagePath = $this->storeImage($image, 'tagLine', 'external');
                $validated['icon'] = config('constants.file_url') . $imagePath;
            }

            $now = now();
            $user = auth()->user();

            $validated['isActive'] = !isset($validated['status']) || $validated['status'] == 1 ? 1 : 0;
            unset($validated['status']);

            $validated['createdBy'] = $user ? $user->id : null;
            $validated['updatedBy'] = null;
            $validated['deletedAt'] = null;
            $validated['deletedBy'] = null;
            $validated['createdAt'] = $now;
            $validated['updatedAt'] = null;

            $data = TagLine::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'TagLine created successfully.',
                'data' => $data
            ], 201);
        }
        catch(Exception $e) 
        {
            Log::error('TagLine creation failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);
            return response()->json([
                'success' => false,
                'message' => 'TagLine creation failed.'
            ], 500);
        }
    }


    public function show($id)
    {
        try 
        {
            $data = TagLine::findOrFail($id);
            $arr = $data->toArray();
            if (!empty($arr['icon']) && !str_starts_with($arr['icon'], 'http')) {
                $arr['icon'] = config('constants.file_url') . $arr['icon'];
            }
            return response()->json(['success' => true, 'data' => $arr], 200);
        }
        catch (Exception $e) 
        {
            return response()->json(['success' => false, 'message' => 'Failed to fetch TagLine.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try 
        {
            $data = TagLine::findOrFail($id);
            $validator = Validator::make($request->all(), [
                'icon' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
                'percentage' => 'sometimes|required|string',
                'description' => 'sometimes|required|string',
                'unit'=> 'nullable|string',
                'isActive' => 'nullable|in:0,1',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }
            $validated = $validator->validated();
            if ($request->hasFile('icon')) {
                $image = $request->file('icon');
                $imagePath = $this->storeImage($image, 'tagLine', 'external');
                $validated['icon'] = config('constants.file_url') . $imagePath;
            }
            // Ensure isActive is stored as integer
            if (isset($validated['isActive'])) {
                $validated['isActive'] = (int) $validated['isActive'];
            }
            $user = auth()->user();
            $now = now();
            $validated['updatedBy'] = $user ? $user->id : null;
            $validated['updatedAt'] = $now;
            $data->update($validated);
            $arr = $data->toArray();
            if (!empty($arr['icon']) && !str_starts_with($arr['icon'], 'http')) {
                $arr['icon'] = config('constants.file_url') . $arr['icon'];
            }
            return response()->json(['success' => true, 'message' => 'TagLine updated successfully.', 'data' => $arr], 200);
        }
        catch(Exception $e) 
        {
            Log::error('TagLine update failed: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e
            ]);
            return response()->json(['success' => false, 'message' => 'Failed to update TagLine.'], 500);
        }
    }

    public function destroy($id)
    {
        try 
        {
            $item = TagLine::withTrashed()->find($id);
            if(!$item) 
            {
                return response()->json(['message' => 'TagLine not found.'], 404);
            }
            $user = auth()->user();
            $now = now();
            if($item->deletedAt) 
            {
                $item->forceDelete();
            } 
            else 
            {
                $item->isActive = 0;
                $item->deletedAt = $now;
                $item->deletedBy = $user ? $user->id : null;
                $item->save();
                $item->delete();
            }
            return response()->json(['message' => 'TagLine deleted successfully.'], 200);
        }
        catch(Exception $e) 
        {
            return response()->json(['success' => false, 'message' => 'Failed to delete TagLine.'], 500);
        }
    }
}