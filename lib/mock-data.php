<?php

$mockUsers = [
  [
    'id' => '1',
    'name' => 'John Smith',
    'email' => 'john@example.com',
    'phone' => '+1-234-567-8900',
    'role' => 'client',
  ],
  [
    'id' => '2',
    'name' => 'Sarah Johnson',
    'email' => 'sarah@example.com',
    'phone' => '+1-234-567-8901',
    'role' => 'client',
  ],
  [
    'id' => 'admin1',
    'name' => 'Resort Manager',
    'email' => 'admin@resort.com',
    'phone' => '+1-234-567-8999',
    'role' => 'admin',
  ],
];

$mockVenues = [
  [
    'id' => '1',
    'name' => 'Grand Ballroom',
    'description' => 'Elegant ballroom perfect for weddings and formal events with crystal chandeliers and marble floors.',
    'capacity' => 200,
    'price' => 5000,
    'amenities' => ['Crystal Chandeliers', 'Dance Floor', 'Stage', 'Sound System', 'Catering Kitchen', 'Bridal Suite'],
    'images' => ['https://images.unsplash.com/photo-1724855946369-9b4612c40fc2?...'],
    'availability' => true,
    'category' => 'ballroom',
  ],
  [
    'id' => '2',
    'name' => 'Oceanview Terrace',
    'description' => 'Stunning outdoor venue with panoramic ocean views, perfect for cocktail parties and receptions.',
    'capacity' => 150,
    'price' => 3500,
    'amenities' => ['Ocean View', 'Outdoor Bar', 'Lounge Areas', 'Fire Pits', 'String Lights', 'Weather Protection'],
    'images' => ['https://images.unsplash.com/photo-1625600879300-d59b96290d03?...'],
    'availability' => true,
    'category' => 'outdoor',
  ],
  [
    'id' => '3',
    'name' => 'Executive Conference Center',
    'description' => 'Modern conference facility equipped with latest technology for corporate events and meetings.',
    'capacity' => 100,
    'price' => 2000,
    'amenities' => ['AV Equipment', 'High-Speed WiFi', 'Video Conferencing', 'Catering Service', 'Break-out Rooms', 'Parking'],
    'images' => ['https://images.unsplash.com/photo-1687945727613-a4d06cc41024?...'],
    'availability' => true,
    'category' => 'conference',
  ],
];

$mockBookings = [
  [
    'id' => '1',
    'userId' => '1',
    'venueId' => '1',
    'date' => '2024-10-15',
    'time' => '18:00',
    'duration' => 5,
    'status' => 'confirmed',
    'paymentStatus' => 'paid',
    'guestCount' => 120,
    'eventType' => 'Wedding',
    'specialRequests' => 'Vegetarian menu preferred',
    'totalAmount' => 5000,
    'createdAt' => '2024-09-15T10:00:00Z',
  ],
  [
    'id' => '2',
    'userId' => '2',
    'venueId' => '2',
    'date' => '2024-10-20',
    'time' => '17:00',
    'duration' => 4,
    'status' => 'pending',
    'paymentStatus' => 'pending',
    'guestCount' => 80,
    'eventType' => 'Corporate Event',
    'totalAmount' => 3500,
    'createdAt' => '2024-09-20T14:30:00Z',
  ],
];

$timeSlots = [
  '09:00', '10:00', '11:00', '12:00', '13:00',
  '14:00', '15:00', '16:00', '17:00', '18:00',
  '19:00', '20:00', '21:00'
];

$eventTypes = [
  'Wedding',
  'Corporate Event',
  'Birthday Party',
  'Anniversary',
  'Conference',
  'Product Launch',
  'Networking Event',
  'Gala Dinner',
  'Award Ceremony',
  'Other'
];