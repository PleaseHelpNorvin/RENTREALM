<?php
namespace Database\Seeders;

use App\Models\Property;
use App\Models\Room;
use App\Models\Address;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PropertyRoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * php artisan db:seed --class=PropertyRoomSeeder
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Ensure the folders exist
        $this->ensureFolderExists('dummy_property_pictures');
        $this->ensureFolderExists('dummy_room_pictures');

        // Get random property images
        $propertyImages = $this->getRandomImagesFromFolder('dummy_property_pictures');
        $roomImages = $this->getRandomImagesFromFolder('dummy_room_pictures');

        // Create 10 properties
        for ($i = 0; $i < 10; $i++) {
            $propertyImage = $faker->randomElement($propertyImages);

            $property = Property::create([
                'name' => $faker->company,
                'property_picture_url' => json_encode([$propertyImage]),
                'gender_allowed' => $faker->randomElement(['boys-only', 'girls-only']),
                'pets_allowed' => $faker->boolean,
                'type' => $faker->randomElement(['apartment', 'house', 'boarding-house']),
                'status' => $faker->randomElement(['available', 'rented', 'full']),
            ]);

            // Create an address for each property
            $property->address()->create([
                'line_1' => $faker->streetAddress,
                'line_2' => $faker->secondaryAddress,
                'province' => $faker->state,
                'country' => $faker->country,
                'postal_code' => $faker->postcode,
            ]);

            // Create at least 5 rooms for each property
            for ($j = 0; $j < 5; $j++) {
                $imageUrls = [
                    $faker->randomElement($roomImages),
                    $faker->randomElement($roomImages),
                    $faker->randomElement($roomImages)
                ];

                $capacity = $faker->numberBetween(1, 5);

                Room::create([
                    'property_id' => $property->id,
                    'room_picture_url' => json_encode($imageUrls),
                    'room_code' => 'room-' . Str::random(6) . rand(100, 999),
                    'description' => $faker->sentence,
                    'room_details' => $faker->sentence,
                    'category' => $faker->word,
                    'rent_price' => $faker->randomFloat(2, 5000, 20000),
                    'capacity' => $capacity,
                    'current_occupants' => $faker->numberBetween(0, $capacity),
                    'min_lease' => $faker->numberBetween(3, 12),
                    'size' => $faker->randomElement(['10ft x 10ft', '12ft x 15ft', '15ft x 20ft']),
                    'status' => $faker->randomElement(['available', 'rented', 'under_maintenance', 'full']),
                    'unit_type' => $faker->randomElement(['studio_unit', 'triplex_unit', 'alcove', 'loft_unit', 'shared_unit', 'micro_unit']),
                ]);
            }
        }
    }

    /**
     * Ensure the folder exists.
     *
     * @param string $folder
     * @return void
     */
    private function ensureFolderExists($folder)
    {
        $folderPath = storage_path('app/public/' . $folder);
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0777, true);
        }
    }

    /**
     * Get all image URLs from a storage folder.
     *
     * @param string $folder
     * @return array
     */
    private function getRandomImagesFromFolder($folder)
    {
        $path = storage_path('app/public/' . $folder);
        if (!file_exists($path)) {
            return [];
        }

        $files = array_diff(scandir($path), ['.', '..']);
        $imageUrls = [];

        foreach ($files as $file) {
            $imageUrls[] = url('http://127.0.0.1:8000/storage/' . $folder . '/' . $file);
        }

        return $imageUrls;
    }
}
