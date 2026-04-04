<?php

namespace App\Http\Controllers;

use App\Models\Car;
use App\Models\CarImage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Car::with(['images', 'user'])
            ->available()
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // Apply filters
        if ($request->location) {
            $query->inLocation($request->location);
        }

        if ($request->car_type) {
            $query->byType($request->car_type);
        }

        if ($request->transmission) {
            $query->byTransmission($request->transmission);
        }

        if ($request->fuel_type) {
            $query->byFuelType($request->fuel_type);
        }

        if ($request->min_price || $request->max_price) {
            $min = $request->min_price ?? 0;
            $max = $request->max_price ?? 50000;
            $query->priceRange($min, $max);
        }

        // Apply sorting
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';

        switch ($sortBy) {
            case 'price_low':
                $query->orderBy('daily_price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('daily_price', 'desc');
                break;
            case 'rating':
                $query->orderBy('reviews_avg_rating', 'desc');
                break;
            case 'recommended':
                $query->orderBy('rating_average', 'desc')
                    ->orderBy('booking_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $cars = $query->paginate($request->per_page ?? 12);

        return response()->json([
            'cars' => $cars->map(function ($car) {
                return [
                    'id' => $car->id,
                    'name' => $car->full_name,
                    'make' => $car->make,
                    'model' => $car->model,
                    'year' => $car->year,
                    'type' => $car->type,
                    'transmission' => $car->transmission,
                    'fuel_type' => $car->fuel_type,
                    'seats' => $car->seats,
                    'location' => $car->location_city,
                    'price' => $car->daily_price,
                    'rating' => round($car->reviews_avg_rating ?? 0, 1),
                    'rating_count' => $car->reviews_count ?? 0,
                    'image' => $car->primary_image_url,
                    'images' => $car->images->map(fn($img) => $img->image_url),
                    'user' => [
                        'id' => $car->user->id,
                        'name' => $car->user->full_name,
                        'image' => $car->user->profile_image_url,
                    ],
                ];
            }),
            'pagination' => [
                'current_page' => $cars->currentPage(),
                'last_page' => $cars->lastPage(),
                'per_page' => $cars->perPage(),
                'total' => $cars->total(),
            ],
        ]);
    }

    public function show(Car $car): JsonResponse
    {
        $car->load(['images', 'user', 'reviews' => function ($query) {
            $query->where('status', 'approved')
                ->with('user')
                ->latest();
        }]);

        return response()->json([
            'car' => [
                'id' => $car->id,
                'name' => $car->full_name,
                'make' => $car->make,
                'model' => $car->model,
                'year' => $car->year,
                'color' => $car->color,
                'type' => $car->type,
                'transmission' => $car->transmission,
                'fuel_type' => $car->fuel_type,
                'seats' => $car->seats,
                'doors' => $car->doors,
                'description' => $car->description,
                'daily_price' => $car->daily_price,
                'weekly_price' => $car->weekly_price,
                'monthly_price' => $car->monthly_price,
                'location_address' => $car->location_address,
                'location_city' => $car->location_city,
                'latitude' => $car->location_latitude,
                'longitude' => $car->location_longitude,
                'features' => $car->features,
                'availability' => $car->availability,
                'rating' => $car->rating_average,
                'rating_count' => $car->rating_count,
                'booking_count' => $car->booking_count,
                'images' => $car->images->map(fn($img) => [
                    'id' => $img->id,
                    'url' => $img->image_url,
                    'is_primary' => $img->is_primary,
                ]),
                'user' => [
                    'id' => $car->user->id,
                    'name' => $car->user->full_name,
                    'image' => $car->user->profile_image_url,
                    'verification_status' => $car->user->verification_status,
                ],
                'reviews' => $car->reviews->map(fn($review) => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user' => [
                        'name' => $review->user->full_name,
                        'image' => $review->user->profile_image_url,
                    ],
                    'created_at' => $review->created_at,
                ]),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'color' => 'required|string|max:255',
            'license_plate' => 'required|string|max:20|unique:cars',
            'vin' => 'required|string|max:17|unique:cars',
            'type' => 'required|in:sedan,suv,hatchback,sports,luxury,truck,van',
            'transmission' => 'required|in:manual,automatic',
            'fuel_type' => 'required|in:petrol,diesel,electric,hybrid',
            'seats' => 'required|integer|min:1|max:8',
            'doors' => 'required|integer|min:2|max:5',
            'description' => 'required|string|max:2000',
            'daily_price' => 'required|numeric|min:0',
            'weekly_price' => 'nullable|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'location_address' => 'required|string|max:500',
            'location_city' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'features' => 'nullable|array',
            'availability' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check user subscription limits
        $user = $request->user();
        if ($user->subscription && !$user->subscription->canListMoreCars()) {
            return response()->json([
                'message' => 'You have reached your car listing limit. Please upgrade your plan.',
            ], 403);
        }

        $car = $user->cars()->create($request->all());

        return response()->json([
            'message' => 'Car listed successfully. It will be reviewed and approved shortly.',
            'car' => [
                'id' => $car->id,
                'name' => $car->full_name,
                'status' => $car->status,
            ],
        ], 201);
    }

    public function update(Request $request, Car $car): JsonResponse
    {
        if ($car->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'make' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'year' => 'sometimes|required|integer|min:1900|max:' . date('Y'),
            'color' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:sedan,suv,hatchback,sports,luxury,truck,van',
            'transmission' => 'sometimes|required|in:manual,automatic',
            'fuel_type' => 'sometimes|required|in:petrol,diesel,electric,hybrid',
            'seats' => 'sometimes|required|integer|min:1|max:8',
            'doors' => 'sometimes|required|integer|min:2|max:5',
            'description' => 'sometimes|required|string|max:2000',
            'daily_price' => 'sometimes|required|numeric|min:0',
            'weekly_price' => 'nullable|numeric|min:0',
            'monthly_price' => 'nullable|numeric|min:0',
            'location_address' => 'sometimes|required|string|max:500',
            'location_city' => 'sometimes|required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'features' => 'nullable|array',
            'availability' => 'nullable|array',
            'is_available' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $car->update($request->all());

        return response()->json([
            'message' => 'Car updated successfully',
            'car' => $car,
        ]);
    }

    public function destroy(Request $request, Car $car): JsonResponse
    {
        if ($car->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if car has active bookings
        if ($car->bookings()->whereIn('status', ['confirmed', 'active'])->exists()) {
            return response()->json([
                'message' => 'Cannot delete car with active bookings',
            ], 422);
        }

        $car->delete();

        return response()->json([
            'message' => 'Car deleted successfully',
        ]);
    }

    public function uploadImages(Request $request, Car $car): JsonResponse
    {
        if ($car->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'images' => 'required|array|max:10',
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $uploadedImages = [];
        $isFirstImage = !$car->images()->exists();

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('car-images', 'public');
            
            $carImage = $car->images()->create([
                'image_path' => $path,
                'image_url' => Storage::url($path),
                'sort_order' => $index,
                'is_primary' => $isFirstImage && $index === 0,
            ]);

            $uploadedImages[] = [
                'id' => $carImage->id,
                'url' => $carImage->image_url,
                'is_primary' => $carImage->is_primary,
            ];
        }

        return response()->json([
            'message' => 'Images uploaded successfully',
            'images' => $uploadedImages,
        ], 201);
    }

    public function deleteImage(Request $request, Car $car, CarImage $carImage): JsonResponse
    {
        if ($car->user_id !== $request->user()->id || $carImage->car_id !== $car->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete from storage
        if ($carImage->image_path) {
            Storage::disk('public')->delete($carImage->image_path);
        }

        $carImage->delete();

        return response()->json([
            'message' => 'Image deleted successfully',
        ]);
    }

    public function pendingCars(): JsonResponse
    {
        $cars = Car::with(['user', 'images'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'cars' => $cars,
        ]);
    }

    public function approveCar(Request $request, Car $car): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'approved' => 'required|boolean',
            'rejection_reason' => 'required_if:approved,false|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->approved) {
            $car->update([
                'status' => 'approved',
                'rejection_reason' => null,
            ]);
        } else {
            $car->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason,
            ]);
        }

        return response()->json([
            'message' => $request->approved ? 'Car approved successfully' : 'Car rejected',
            'car' => $car,
        ]);
    }
}
