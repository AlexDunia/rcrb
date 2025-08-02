<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Property;
use App\Models\ResidentialProperty;
use App\Models\PropertyMedia;
use App\Models\Amenity;
use App\Models\Feature;
use Illuminate\Support\Facades\Hash;

class NewPropertiesSeeder extends Seeder
{
    private $imageUrls = [
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1747368015/BG_eovnvt.png',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1747368015/Image_lyj6xv.png',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1754008495/e6c5c60a-8ca2-4b1d-8e96-6ac776caec4a_c48rb5.jpg',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1754008475/25a945bb-f499-4d46-ad6a-eed2dab7c035_jc4xjp.jpg',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1754008457/b8f823fa-45a5-4275-a599-bfe7c1344f23_jpg4ti.jpg',
        'https://res.cloudinary.com/dnuhjsckk/image/upload/v1754008451/3cd3bf9f-3637-4dcd-8bdf-fab7b8cd787c_qcmxkk.jpg'
    ];

    public function run(): void
    {
        // Create or get test agent
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

        // Properties data
        $properties = [
            [
                'name' => 'Luxurious Waterfront Villa',
                'description' => 'Stunning waterfront villa with panoramic views, featuring modern architecture and premium finishes throughout. This exceptional property offers resort-style living with private beach access.',
                'price' => 2850000,
                'address' => '789 Oceanview Drive',
                'city' => 'Miami',
                'state' => 'FL',
                'zip_code' => '33139',
                'type' => 'villa',
                'status' => 'for_sale',
                'bedrooms' => 5,
                'bathrooms' => 5.5,
                'size' => 6500,
                'year_built' => 2022,
                'neighborhood' => 'South Beach',
                'latitude' => 25.7617,
                'longitude' => -80.1918,
                'residential_type' => 'villa',
                'total_rooms' => 12,
                'stories' => 2,
                'basement' => true,
                'garage' => true,
                'garage_size' => 3,
                'features' => ['Smart Home System', 'Floor-to-ceiling Windows', 'High Ceilings', 'Fireplace'],
                'amenities' => ['Swimming Pool', 'Garden', 'Rooftop Deck', '24/7 Security']
            ],
            [
                'name' => 'Modern Urban Penthouse',
                'description' => 'Spectacular penthouse in the heart of downtown with floor-to-ceiling windows offering breathtaking city views. Features include a private elevator and expansive terrace.',
                'price' => 1950000,
                'address' => '567 Downtown Avenue',
                'city' => 'Chicago',
                'state' => 'IL',
                'zip_code' => '60601',
                'type' => 'condo',
                'status' => 'for_sale',
                'bedrooms' => 3,
                'bathrooms' => 3.5,
                'size' => 3200,
                'year_built' => 2021,
                'neighborhood' => 'Loop',
                'latitude' => 41.8781,
                'longitude' => -87.6298,
                'residential_type' => 'condo',
                'total_rooms' => 8,
                'stories' => 1,
                'basement' => false,
                'garage' => true,
                'garage_size' => 2,
                'features' => ['Hardwood Floors', 'Granite Countertops', 'Smart Home System', 'Walk-in Closet'],
                'amenities' => ['Fitness Center', 'Elevator', '24/7 Security', 'Rooftop Deck']
            ],
            [
                'name' => 'Classic Colonial Estate',
                'description' => 'Magnificent colonial estate set on 2 acres of manicured grounds. This timeless property combines traditional architecture with modern amenities.',
                'price' => 1750000,
                'address' => '123 Heritage Lane',
                'city' => 'Greenwich',
                'state' => 'CT',
                'zip_code' => '06830',
                'type' => 'single_family',
                'status' => 'for_sale',
                'bedrooms' => 6,
                'bathrooms' => 4.5,
                'size' => 5800,
                'year_built' => 1995,
                'neighborhood' => 'Belle Haven',
                'latitude' => 41.0262,
                'longitude' => -73.6282,
                'residential_type' => 'single_family',
                'total_rooms' => 14,
                'stories' => 3,
                'basement' => true,
                'garage' => true,
                'garage_size' => 3,
                'features' => ['Hardwood Floors', 'Fireplace', 'High Ceilings', 'Walk-in Closet'],
                'amenities' => ['Swimming Pool', 'Garden', 'Pet Friendly', 'Parking Garage']
            ],
            [
                'name' => 'Contemporary Townhouse',
                'description' => 'Sleek and sophisticated townhouse featuring an open concept design and premium finishes. Perfect blend of comfort and modern urban living.',
                'price' => 1250000,
                'address' => '456 Metropolitan Way',
                'city' => 'Boston',
                'state' => 'MA',
                'zip_code' => '02116',
                'type' => 'townhouse',
                'status' => 'for_sale',
                'bedrooms' => 4,
                'bathrooms' => 3.5,
                'size' => 3000,
                'year_built' => 2020,
                'neighborhood' => 'Back Bay',
                'latitude' => 42.3601,
                'longitude' => -71.0589,
                'residential_type' => 'townhouse',
                'total_rooms' => 10,
                'stories' => 3,
                'basement' => true,
                'garage' => true,
                'garage_size' => 2,
                'features' => ['Smart Home System', 'Granite Countertops', 'Floor-to-ceiling Windows', 'Hardwood Floors'],
                'amenities' => ['Pet Friendly', 'Garden', 'Parking Garage', 'Rooftop Deck']
            ],
            [
                'name' => 'Luxury Sky Apartment',
                'description' => 'High-rise luxury apartment offering panoramic city views and world-class amenities. Features designer finishes and smart home technology throughout.',
                'price' => 1650000,
                'address' => '789 Skyline Boulevard',
                'city' => 'Seattle',
                'state' => 'WA',
                'zip_code' => '98101',
                'type' => 'apartment',
                'status' => 'for_sale',
                'bedrooms' => 3,
                'bathrooms' => 2.5,
                'size' => 2800,
                'year_built' => 2023,
                'neighborhood' => 'Downtown',
                'latitude' => 47.6062,
                'longitude' => -122.3321,
                'residential_type' => 'apartment',
                'total_rooms' => 7,
                'stories' => 1,
                'basement' => false,
                'garage' => true,
                'garage_size' => 2,
                'features' => ['Floor-to-ceiling Windows', 'Smart Home System', 'Granite Countertops', 'Walk-in Closet'],
                'amenities' => ['Fitness Center', 'Swimming Pool', '24/7 Security', 'Elevator']
            ]
        ];

        foreach ($properties as $propertyData) {
            $features = $propertyData['features'];
            $amenities = $propertyData['amenities'];
            $residential_type = $propertyData['residential_type'];

            // Remove extra fields before creating property
            unset(
                $propertyData['features'],
                $propertyData['amenities'],
                $propertyData['residential_type'],
                $propertyData['total_rooms'],
                $propertyData['stories'],
                $propertyData['basement'],
                $propertyData['garage'],
                $propertyData['garage_size']
            );

            // Create main property
            $property = Property::create(array_merge($propertyData, ['agent_id' => $agent->id]));

            // Create residential property details
            ResidentialProperty::create([
                'property_id' => $property->id,
                'residential_type' => $residential_type,
                'bedrooms' => $propertyData['bedrooms'],
                'bathrooms' => (int)$propertyData['bathrooms'],
                'total_rooms' => $propertyData['total_rooms'] ?? 0,
                'floor_area' => $propertyData['size'],
                'stories' => $propertyData['stories'] ?? 1,
                'basement' => $propertyData['basement'] ?? false,
                'garage' => $propertyData['garage'] ?? false,
                'garage_size' => $propertyData['garage_size'] ?? null
            ]);

            // Attach features
            $featureIds = Feature::whereIn('name', $features)->pluck('id');
            $property->features()->attach($featureIds);

            // Attach amenities
            $amenityIds = Amenity::whereIn('name', $amenities)->pluck('id');
            $property->amenities()->attach($amenityIds);

            // Add images (3-4 random images per property)
            $numImages = rand(3, 4);
            $selectedImages = array_rand(array_flip($this->imageUrls), $numImages);

            if (!is_array($selectedImages)) {
                $selectedImages = [$selectedImages];
            }

            foreach ($selectedImages as $index => $imageUrl) {
                PropertyMedia::create([
                    'media_key' => Str::uuid(),
                    'property_id' => $property->id,
                    'resource_name' => 'Property',
                    'resource_record_key' => (string)$property->id,
                    'media_type' => 'image',
                    'media_category' => $index === 0 ? 'Primary' : 'Secondary',
                    'image_size_description' => 'Large',
                    'media_url' => $imageUrl,
                    'media_caption' => 'Property Image',
                    'media_description' => 'Property view',
                    'is_active' => true,
                    'order_index' => $index
                ]);
            }
        }
    }
}
