<?php

namespace Modules\BannerManagement\Http\Controllers;

use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Modules\BannerManagement\Entities\Banner;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Support\Facades\Log;

class BannerController extends Controller
{

    use FileUploadTrait;

    public function index()
    {
        try 
        {
            $list = Banner::all();
            // Ensure imageUrl is full URL in all banners
            $list = $list->map(function($item) {
                $arr = $item->toArray();
                if (!empty($arr['imageUrl']) && !str_starts_with($arr['imageUrl'], 'http')) {
                    $arr['imageUrl'] = config('constants.file_url') . $arr['imageUrl'];
                }
                return $arr;
            });
            if($list->isEmpty()) 
            {
                return response()->json([
                    'success' => true,
                    'message' => 'No banners found.',
                    'data' => []
                ], 200);
            }
            return response()->json(['success' => true, 'data' => $list], 200);
        }
        catch(Exception $e)
        {
            return response()->json(['success' => false, 'message' => 'Failed to fetch banners.'], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'imageUrl' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
                'heading' => 'nullable|string|max:255',
                'subheading' => 'nullable|string|max:255',
                'textColor' => 'nullable|string|in:white,black',
                'status' => 'nullable|in:0,1',
            ]);

            if ($request->hasFile('imageUrl')) {
                $image = $request->file('imageUrl');
                $imagePath = $this->storeImage($image, 'banners', 'external');
                $validatedData['imageUrl'] = config('constants.file_url') . $imagePath;
            }

            $validatedData['textColor'] = $validatedData['textColor'] ?? 'black';

            $validatedData['isActive'] = !isset($validatedData['status']) || $validatedData['status'] == 1 ? 1 : 0;
            unset($validatedData['status']);

            $user = auth()->user();
            $now = now();

            $validatedData += [
                'createdBy'  => $user?->id,
                'updatedBy'  => null,
                'deletedAt'  => null,
                'deletedBy'  => null,
                'createdAt'  => $now,
                'updatedAt'  => null,
            ];

            $data = Banner::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Banner created successfully.',
                'data' => $data->toArray()
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (Exception $e) {
            Log::error('Failed to create Banner:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Banner creation failed.'], 500);
        }
    }

    public function show($id)
    {
        try 
        {
            $data = Banner::findOrFail($id);
            $arr = $data->toArray();
            if (!empty($arr['imageUrl']) && !str_starts_with($arr['imageUrl'], 'http')) {
                $arr['imageUrl'] = config('constants.file_url') . $arr['imageUrl'];
            }
            return response()->json(['success' => true, 'data' => $arr], 200);
        }
        catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) 
        {
            return response()->json(['success' => false, 'message' => 'Banner not found.'], 404);
        }
        catch (Exception $e) 
        {
            Log::error('Failed to fetch Banner:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An unexpected error occurred while fetching the Banner.'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try 
        {
            $data = Banner::findOrFail($id);
            $validatedData = $request->validate([
                'imageUrl' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10240',
                'heading' => 'nullable|string|max:255',
                'subheading' => 'nullable|string|max:255',
                'textColor' => 'nullable|string|in:white,black',
                'isActive' => 'nullable|integer|in:0,1',
            ]);
            if ($request->hasFile('imageUrl')) {
                $image = $request->file('imageUrl');
                $imagePath = $this->storeImage($image, 'banners', 'external');
                $fullImageUrl = config('constants.file_url') . $imagePath;
                $validatedData['imageUrl'] = $fullImageUrl;
            }
            // Ensure isActive is stored as integer
            if (isset($validatedData['isActive'])) {
                $validatedData['isActive'] = (int) $validatedData['isActive'];
            }
            // Set default for textColor if not provided
            $validatedData['textColor'] = $validatedData['textColor'] ?? $data->textColor ?? 'black';
            $user = auth()->user();
            $now = now();
            $validatedData['updatedBy'] = $user?->id;
            $validatedData['updatedAt'] = $now;
            $data->update($validatedData);
            $arr = $data->toArray();
            if (!empty($arr['imageUrl']) && !str_starts_with($arr['imageUrl'], 'http')) {
                $arr['imageUrl'] = config('constants.file_url') . $arr['imageUrl'];
            }
            return response()->json(['success' => true, 'message' => 'Banner updated successfully.', 'data' => $arr], 200);
        }
        catch(\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        }
        catch(Exception $e) 
        {
            Log::error('Failed to update Banner:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to update Banner.'], 500);
        }
    }

    public function destroy($id)
    {
        try 
        {
            $item = Banner::withTrashed()->find($id);
            if(!$item) 
            {
                return response()->json(['message' => 'Banner not found.'], 404);
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
                $item->deletedBy = $user?->id;
                $item->save();
                $item->delete();
            }
            return response()->json(['message' => 'Banner deleted successfully.'], 200);
        }
        catch(Exception $e) 
        {
            return response()->json(['success' => false, 'message' => 'Failed to delete Banner.'], 500);
        }
    }
}