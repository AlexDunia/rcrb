<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Property;
use App\Models\ResidentialProperty;
use App\Models\CommercialProperty;
use App\Models\LandProperty;
use App\Models\PropertyMedia;
use App\Models\Amenity;
use App\Models\Feature;
use Illuminate\Support\Facades\Hash;

class PropertySeeder extends Seeder
{
    private $imageUrls = [
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1747368015/BG_eovnvt.png',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1747368015/Image_lyj6xv.png',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1743087291/Designer_8_1_fjvyi0.png',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1739548295/newtwoimage1_2_ti4hfi.png',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1739548284/Rectangle_227_ncwnmz.png',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1718268643/newtwoimage_yt8arg.jpg'
    ];

    public function run(): void
    {
        // Create a test agent
        $agent = User::firstOrCreate(
            ['email' => 'john.smith@realcity.com'],
            [
                'name' => 'John Smith',
                'password' => Hash::make('password'),
                'phone' => '(555) 123-4567',
                'profile_photo_url' => 'https://example.com/agent-john.jpg',
                'rating' => 4.8
            ]
        );

        // Create features
        $features = [
            'Hardwood Floors',
            'Granite Countertops',
            'Floor-to-ceiling Windows',
            'Smart Home System',
            'Central Air',
            'Walk-in Closet',
            'Fireplace',
            'High Ceilings'
        ];

        foreach ($features as $feature) {
            Feature::firstOrCreate(['name' => $feature]);
        }

        // Create amenities
        $amenities = [
            'Swimming Pool',
            'Fitness Center',
            '24/7 Security',
            'Parking Garage',
            'Pet Friendly',
            'Elevator',
            'Garden',
            'Rooftop Deck'
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(['name' => $amenity]);
        }

        // Create sample properties
        $properties = [
            [
                'name' => 'Luxury Downtown Condo',
                'description' => 'Beautiful modern condo in the heart of downtown',
                'price' => 850000,
                'address' => '123 Main Street',
                'city' => 'New York',
                'state' => 'NY',
                'zip_code' => '10001',
                'type' => 'condo',
                'status' => 'for_sale',
                'bedrooms' => 3,
                'bathrooms' => 2.5,
                'size' => 2100,
                'year_built' => 2020,
                'neighborhood' => 'Financial District',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'virtual_tour_url' => 'https://example.com/virtual-tour/condo-123',
                'features' => ['Hardwood Floors', 'Granite Countertops', 'Floor-to-ceiling Windows', 'Smart Home System'],
                'amenities' => ['Swimming Pool', 'Fitness Center', '24/7 Security', 'Parking Garage'],
                'images' => [
                    'https://example.com/luxury-condo-1.jpg',
                    'https://example.com/luxury-condo-2.jpg',
                    'https://example.com/luxury-condo-3.jpg'
                ]
            ],
            [
                'name' => 'Modern Townhouse',
                'description' => 'Spacious townhouse with modern amenities',
                'price' => 1200000,
                'address' => '456 Park Avenue',
                'city' => 'New York',
                'state' => 'NY',
                'zip_code' => '10002',
                'type' => 'townhouse',
                'status' => 'for_sale',
                'bedrooms' => 4,
                'bathrooms' => 3.5,
                'size' => 3200,
                'year_built' => 2019,
                'neighborhood' => 'Upper East Side',
                'latitude' => 40.7736,
                'longitude' => -73.9566,
                'virtual_tour_url' => 'https://example.com/virtual-tour/townhouse-456',
                'features' => ['Smart Home System', 'Fireplace', 'High Ceilings', 'Walk-in Closet'],
                'amenities' => ['Pet Friendly', 'Garden', 'Parking Garage'],
                'images' => [
                    'https://example.com/townhouse-1.jpg',
                    'https://example.com/townhouse-2.jpg'
                ]
            ]
        ];

        foreach ($properties as $propertyData) {
            $features = $propertyData['features'];
            $amenities = $propertyData['amenities'];
            $images = $propertyData['images'];

            unset($propertyData['features'], $propertyData['amenities'], $propertyData['images']);

            $property = Property::create(array_merge($propertyData, ['agent_id' => $agent->id]));

            // Attach features
            $featureIds = Feature::whereIn('name', $features)->pluck('id');
            $property->features()->attach($featureIds);

            // Attach amenities
            $amenityIds = Amenity::whereIn('name', $amenities)->pluck('id');
            $property->amenities()->attach($amenityIds);

            // Add images
            foreach ($images as $imageUrl) {
                PropertyMedia::create([
                    'property_id' => $property->id,
                    'url' => $imageUrl,
                    'type' => 'image'
                ]);
            }
        }
    }

    private function createResidentialProperties($agent)
    {
        $types = ['single_family', 'apartment', 'townhouse', 'duplex', 'condo', 'villa'];

        foreach ($types as $index => $type) {
            $property = Property::create([
                'agent_id' => $agent->id,
                'property_type' => 'residential',
                'status' => 'active',
                'title' => ucwords(str_replace('_', ' ', $type)) . ' Home',
                'slug' => Str::slug(ucwords(str_replace('_', ' ', $type)) . ' Home-' . uniqid()),
                'description' => 'Beautiful ' . str_replace('_', ' ', $type) . ' property with modern amenities',
                'price' => rand(200000, 1500000),
                'address_line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country' => 'United States',
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
                'lot_size' => rand(1000, 10000),
                'year_built' => rand(1990, 2023),
                'parking_spots' => rand(1, 4)
            ]);

            ResidentialProperty::create([
                'property_id' => $property->id,
                'residential_type' => $type,
                'bedrooms' => rand(1, 6),
                'bathrooms' => rand(1, 4),
                'total_rooms' => rand(4, 12),
                'floor_area' => rand(800, 5000),
                'stories' => rand(1, 3),
                'basement' => fake()->boolean(),
                'garage' => fake()->boolean(),
                'garage_size' => rand(1, 3)
            ]);

            // Add media
            $this->addPropertyMedia($property);
        }
    }

    private function createCommercialProperties($agent)
    {
        $types = ['office', 'retail', 'industrial', 'warehouse', 'restaurant', 'mixed_use'];

        foreach ($types as $index => $type) {
            $property = Property::create([
                'agent_id' => $agent->id,
                'property_type' => 'commercial',
                'status' => 'active',
                'title' => ucwords(str_replace('_', ' ', $type)) . ' Space',
                'slug' => Str::slug(ucwords(str_replace('_', ' ', $type)) . ' Space-' . uniqid()),
                'description' => 'Prime ' . str_replace('_', ' ', $type) . ' property in excellent location',
                'price' => rand(500000, 5000000),
                'address_line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country' => 'United States',
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
                'lot_size' => rand(5000, 50000),
                'year_built' => rand(1980, 2023),
                'parking_spots' => rand(10, 100)
            ]);

            CommercialProperty::create([
                'property_id' => $property->id,
                'commercial_type' => $type,
                'total_area' => rand(2000, 20000),
                'floors' => rand(1, 20),
                'units' => rand(1, 50),
                'loading_docks' => rand(0, 5),
                'ceiling_height' => rand(8, 20),
                'zoning' => 'Commercial',
                'current_use' => $type,
                'potential_use' => implode(', ', array_rand(array_flip($types), 3))
            ]);

            // Add media
            $this->addPropertyMedia($property);
        }
    }

    private function createLandProperties($agent)
    {
        $types = ['residential_lot', 'commercial_lot', 'agricultural', 'recreational', 'industrial_lot'];

        foreach ($types as $index => $type) {
            $property = Property::create([
                'agent_id' => $agent->id,
                'property_type' => 'land',
                'status' => 'active',
                'title' => ucwords(str_replace('_', ' ', $type)),
                'slug' => Str::slug(ucwords(str_replace('_', ' ', $type)) . '-' . uniqid()),
                'description' => 'Prime ' . str_replace('_', ' ', $type) . ' land for development',
                'price' => rand(100000, 2000000),
                'address_line1' => fake()->streetAddress(),
                'city' => fake()->city(),
                'state' => fake()->state(),
                'postal_code' => fake()->postcode(),
                'country' => 'United States',
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
                'lot_size' => rand(10000, 1000000),
                'year_built' => null,
                'parking_spots' => 0
            ]);

            LandProperty::create([
                'property_id' => $property->id,
                'land_type' => $type,
                'topography' => fake()->randomElement(['Flat', 'Sloped', 'Hilly', 'Mixed']),
                'soil_type' => fake()->randomElement(['Clay', 'Sandy', 'Loam', 'Mixed']),
                'utilities_available' => fake()->boolean(),
                'road_frontage' => rand(50, 500),
                'zoning' => ucfirst($type),
                'current_use' => 'Vacant',
                'potential_use' => implode(', ', array_rand(array_flip($types), 2))
            ]);

            // Add media
            $this->addPropertyMedia($property);
        }
    }

    private function addPropertyMedia($property)
    {
        // Add 1-3 random images from our pool
        $numImages = rand(1, 3);
        $selectedImages = array_rand(array_flip($this->imageUrls), $numImages);

        if (!is_array($selectedImages)) {
            $selectedImages = [$selectedImages];
        }

        foreach ($selectedImages as $imageUrl) {
            PropertyMedia::create([
                'property_id' => $property->id,
                'media_type' => 'image',
                'url' => $imageUrl,
                'is_featured' => false,
                'title' => 'Property Image',
                'description' => 'Property view'
            ]);
        }
    }
}
