<?php

namespace App\Services;

use App\Models\Car;
use App\Models\CarImage;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\UploadedFile;

class CarService
{
    /**
     * Create a new car
     */
    public function create(array $data, User $user): Car
    {
        try {
            $validator = Validator::make($data, [
                'make' => 'required|string|max:255',
                'model' => 'required|string|max:255',
                'year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
                'type' => 'required|string|in:sedan,suv,coupe,convertible,truck,van,luxury,sports',
                'transmission' => 'required|string|in:manual,automatic',
                'fuel_type' => 'required|string|in:petrol,diesel,electric,hybrid',
                'seats' => 'required|integer|min:1|max:20',
                'daily_price' => 'required|numeric|min:0',
                'location' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'features' => 'nullable|array',
                'availability_status' => 'required|string|in:available,unavailable,maintenance',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $car = $user->cars()->create([
                'make' => $data['make'],
                'model' => $data['model'],
                'year' => $data['year'],
                'type' => $data['type'],
                'transmission' => $data['transmission'],
                'fuel_type' => $data['fuel_type'],
                'seats' => $data['seats'],
                'daily_price' => $data['daily_price'],
                'location' => $data['location'],
                'description' => $data['description'],
                'features' => $data['features'] ?? [],
                'availability_status' => $data['availability_status'],
                'status' => 'pending', // Requires admin approval
            ]);

            return $car;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update a car
     */
    public function update(Car $car, array $data, User $user): Car
    {
        try {
            // Check if user owns the car or is admin
            if ($car->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'car' => ['You are not authorized to update this car.'],
                ]);
            }

            $validator = Validator::make($data, [
                'make' => 'sometimes|required|string|max:255',
                'model' => 'sometimes|required|string|max:255',
                'year' => 'sometimes|required|integer|min:1900|max:' . (date('Y') + 1),
                'type' => 'sometimes|required|string|in:sedan,suv,coupe,convertible,truck,van,luxury,sports',
                'transmission' => 'sometimes|required|string|in:manual,automatic',
                'fuel_type' => 'sometimes|required|string|in:petrol,diesel,electric,hybrid',
                'seats' => 'sometimes|required|integer|min:1|max:20',
                'daily_price' => 'sometimes|required|numeric|min:0',
                'location' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string|max:2000',
                'features' => 'sometimes|array',
                'availability_status' => 'sometimes|required|string|in:available,unavailable,maintenance',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $car->update($data);

            return $car->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete a car
     */
    public function delete(Car $car, User $user): bool
    {
        try {
            // Check if user owns the car or is admin
            if ($car->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'car' => ['You are not authorized to delete this car.'],
                ]);
            }

            // Delete car images
            foreach ($car->images as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }

            return $car->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Upload car images
     */
    public function uploadImages(Car $car, array $images, User $user): array
    {
        try {
            // Check if user owns the car or is admin
            if ($car->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'car' => ['You are not authorized to upload images for this car.'],
                ]);
            }

            $uploadedImages = [];
            $maxImages = 10; // Maximum 10 images per car

            if (count($car->images) >= $maxImages) {
                throw ValidationException::withMessages([
                    'images' => ['Maximum number of images (' . $maxImages . ') reached.'],
                ]);
            }

            foreach ($images as $image) {
                if (!$image instanceof UploadedFile) {
                    continue;
                }

                $validator = Validator::make(['image' => $image], [
                    'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
                ]);

                if ($validator->fails()) {
                    continue; // Skip invalid images
                }

                $path = $image->store('car-images', 'public');
                
                $carImage = $car->images()->create([
                    'image_path' => $path,
                    'is_primary' => $car->images()->count() === 0, // First image is primary
                ]);

                $uploadedImages[] = $carImage;
            }

            return $uploadedImages;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete car image
     */
    public function deleteImage(CarImage $image, User $user): bool
    {
        try {
            // Check if user owns the car or is admin
            if ($image->car->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'image' => ['You are not authorized to delete this image.'],
                ]);
            }

            // Delete file from storage
            Storage::disk('public')->delete($image->image_path);

            // If this was primary, set another image as primary
            if ($image->is_primary) {
                $nextImage = $image->car->images()->where('id', '!=', $image->id)->first();
                if ($nextImage) {
                    $nextImage->update(['is_primary' => true]);
                }
            }

            return $image->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Update car availability
     */
    public function updateAvailability(Car $car, string $status, User $user): Car
    {
        try {
            // Check if user owns the car or is admin
            if ($car->user_id !== $user->id && !$user->hasRole('admin')) {
                throw ValidationException::withMessages([
                    'car' => ['You are not authorized to update this car.'],
                ]);
            }

            $validator = Validator::make(['status' => $status], [
                'status' => 'required|string|in:available,unavailable,maintenance',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $car->update(['availability_status' => $status]);

            return $car->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get pending cars for admin
     */
    public function getPendingCars(): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return Car::with(['user', 'images'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Approve a car
     */
    public function approveCar(Car $car): Car
    {
        try {
            $car->update(['status' => 'approved']);
            return $car->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Reject a car
     */
    public function rejectCar(Car $car, string $reason = null): Car
    {
        try {
            $car->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);
            return $car->fresh();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
