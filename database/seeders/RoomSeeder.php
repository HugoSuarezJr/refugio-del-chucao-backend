<?php

namespace Database\Seeders;

use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            [
                'name' => 'Martín Pescador',
                'slug' => 'martin-pescador',
                'subtitle' => 'Habitación matrimonial con vista al lago',
                'description' => 'Nuestra habitación más completa, con una hermosa vista al lago, cocina privada totalmente equipada y un acogedor balcón donde podrás disfrutar del amanecer sobre el agua. Ideal para quienes buscan comodidad y una conexión íntima con la naturaleza.',
                'bed_type' => '1 cama doble',
                'capacity' => 2,
                'size' => '404 pies²',
                'base_nightly_rate' => 100000,
                'amenities' => [
                    ['icon' => 'Bed', 'label' => 'Cama doble'],
                    ['icon' => 'Bath', 'label' => 'Baño privado'],
                    ['icon' => 'UtensilsCrossed', 'label' => 'Cocina privada'],
                    ['icon' => 'Mountain', 'label' => 'Vista al lago'],
                    ['icon' => 'Tv', 'label' => 'TV pantalla plana'],
                    ['icon' => 'Wifi', 'label' => 'Wifi gratis'],
                    ['icon' => 'Thermometer', 'label' => 'Calefacción'],
                    ['icon' => 'Accessibility', 'label' => 'Accesible'],
                ],
                'kitchen_amenities' => ['Refrigerador', 'Tetera / hervidor eléctrico', 'Cafetera / té', 'Microondas', 'Utensilios de cocina', 'Horno', 'Minibar', 'Comedor / mesa de comedor'],
                'bathroom_amenities' => ['Inodoro', 'Tina o ducha', 'Secador de pelo', 'Papel higiénico'],
                'other_amenities' => ['Escritorio', 'Piso de madera', 'Toallas', 'Zona de estar', 'Enchufe cerca de la cama', 'Ropa de cama', 'Streaming (Netflix)', 'Kitchenette', 'Calefacción', 'Lavadora', 'Accesible para silla de ruedas', 'Planta baja', 'Adaptada para discapacidad auditiva', 'Balcón', 'Terraza', 'Minibar'],
                'policies' => ['No fumar'],
                'highlights' => ['Vista al lago', 'Cocina privada', 'Accesible', 'Balcón'],
                'main_image_url' => '/images/rooms/martin_pescador_1.jpeg',
                'gallery_images' => ['/images/rooms/martin_pescador_1.jpeg', '/images/rooms/martin_pescador_2.jpeg'],
                'sort_order' => 1,
            ],
            [
                'name' => 'Siete Colores',
                'slug' => 'siete-colores',
                'subtitle' => 'Habitación matrimonial acogedora con acceso al lago',
                'description' => 'Una habitación cálida y acogedora con acceso directo al lago. Disfruta de la tranquilidad del entorno natural, con todas las comodidades necesarias para una estadía inolvidable en la Patagonia.',
                'bed_type' => '1 cama doble',
                'capacity' => 2,
                'base_nightly_rate' => 100000,
                'amenities' => [
                    ['icon' => 'Bed', 'label' => 'Cama doble'],
                    ['icon' => 'Bath', 'label' => 'Baño privado'],
                    ['icon' => 'UtensilsCrossed', 'label' => 'Cocina'],
                    ['icon' => 'Waves', 'label' => 'Acceso al lago'],
                    ['icon' => 'Tv', 'label' => 'TV'],
                    ['icon' => 'Wifi', 'label' => 'Wifi'],
                    ['icon' => 'Lock', 'label' => 'Cerradura privada'],
                    ['icon' => 'Briefcase', 'label' => 'Espacio de trabajo'],
                ],
                'policies' => ['No fumar'],
                'highlights' => ['Acceso al lago', 'Cocina', 'Espacio de trabajo'],
                'main_image_url' => '/images/rooms/siete_colores_1.jpeg',
                'gallery_images' => ['/images/rooms/siete_colores_1.jpeg', '/images/rooms/siete_colores_2.jpeg'],
                'sort_order' => 2,
            ],
            [
                'name' => 'La Bandurria',
                'slug' => 'la-bandurria',
                'subtitle' => 'Habitación matrimonial simple y cómoda',
                'description' => 'Sencilla, cálida y perfecta para descansar. La Bandurria ofrece todo lo esencial en un ambiente acogedor de madera nativa, ideal para quienes buscan tranquilidad y simplicidad.',
                'bed_type' => '1 cama doble',
                'capacity' => 2,
                'base_nightly_rate' => 100000,
                'amenities' => [
                    ['icon' => 'Bed', 'label' => 'Cama doble'],
                    ['icon' => 'Bath', 'label' => 'Baño privado'],
                    ['icon' => 'Wifi', 'label' => 'Wifi gratis'],
                ],
                'policies' => ['No fumar'],
                'highlights' => ['Simple y acogedora', 'Baño privado', 'Wifi'],
                'main_image_url' => '/images/rooms/la_bandurria_1.jpeg',
                'gallery_images' => ['/images/rooms/la_bandurria_1.jpeg', '/images/rooms/la_bandurria_2.jpeg'],
                'sort_order' => 3,
            ],
            [
                'name' => 'Las Taguas',
                'slug' => 'las-taguas',
                'subtitle' => 'Habitación con dos camas individuales y vista al lago',
                'description' => 'Con dos camas individuales y una vista privilegiada al lago, Las Taguas es ideal para amigos o familiares que desean disfrutar de la naturaleza con todas las comodidades, incluyendo cocina privada equipada.',
                'bed_type' => '2 camas individuales',
                'capacity' => 2,
                'size' => '404 pies²',
                'base_nightly_rate' => 100000,
                'amenities' => [
                    ['icon' => 'Bed', 'label' => '2 camas individuales'],
                    ['icon' => 'Bath', 'label' => 'Baño privado'],
                    ['icon' => 'UtensilsCrossed', 'label' => 'Cocina privada'],
                    ['icon' => 'Mountain', 'label' => 'Vista al lago'],
                    ['icon' => 'Wifi', 'label' => 'Wifi gratis'],
                    ['icon' => 'Thermometer', 'label' => 'Calefacción'],
                ],
                'kitchen_amenities' => ['Refrigerador', 'Tetera / hervidor eléctrico', 'Cafetera / té', 'Microondas', 'Utensilios de cocina', 'Horno', 'Minibar', 'Comedor / mesa de comedor'],
                'bathroom_amenities' => ['Inodoro', 'Tina o ducha', 'Secador de pelo', 'Papel higiénico'],
                'other_amenities' => ['Escritorio', 'Piso de madera', 'Mesa de comedor', 'Acceso por escaleras', 'Toallas', 'Zona de estar', 'Enchufe cerca de la cama', 'Ropa de cama', 'Streaming (Netflix)', 'Kitchenette', 'Calefacción', 'Lavadora', 'Adaptada para discapacidad auditiva'],
                'policies' => ['No fumar'],
                'highlights' => ['Vista al lago', 'Cocina privada', '2 camas individuales'],
                'main_image_url' => '/images/rooms/las_taguas_1.jpg',
                'gallery_images' => ['/images/rooms/las_taguas_1.jpg'],
                'sort_order' => 4,
            ],
        ];

        foreach ($rooms as $room) {
            Room::query()->updateOrCreate(
                ['slug' => $room['slug']],
                array_merge($room, ['currency' => config('booking.currency')]),
            );
        }
    }
}
