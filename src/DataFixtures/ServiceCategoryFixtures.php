<?php

namespace App\DataFixtures;

use App\Entity\ServiceCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ServiceCategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['name' => 'Barber Shop', 'imageUrl' => 'https://gyazo.com/069e951e943159bb845445c9251ba0a5.png'],
            ['name' => 'Fryzjer', 'imageUrl' => 'https://i.gyazo.com/9b2ac4fadd55a64ba2eb6ffef6543b4e.png'],
            ['name' => 'Trening i dieta', 'imageUrl' => 'https://i.gyazo.com/3f1f3d8822bdc87c58115c7717484d4d.png'],
            ['name' => 'Masaż', 'imageUrl' => 'https://i.gyazo.com/2b65f423ed8d24cf5318b9bd6a0f89ce.png'],
            ['name' => 'Fizjoterapia', 'imageUrl' => 'https://i.gyazo.com/b3cf764fcf72322f3b3029ea003ad5aa.png'],
            ['name' => 'Salon Kosmetyczny', 'imageUrl' => 'https://i.gyazo.com/47582492dc8f12eb8704ed54bea77856.png'],
            ['name' => 'Tatuaż i Piercing', 'imageUrl' => 'https://i.gyazo.com/839ffb518bed761d5bb12358ecb7c81d.png'],
            ['name' => 'Stomatolog', 'imageUrl' => 'https://i.gyazo.com/8cf51bc73202bbe0c645d98f5992d0e3.png'],
            ['name' => 'Medycyna estetyczna', 'imageUrl' => 'https://i.gyazo.com/8736a23d287dd7526367768162abf724.png'],
            ['name' => 'Paznokcie', 'imageUrl' => 'https://i.gyazo.com/933229eb1f2a200598f6d45b030ca7a5.png'],
            ['name' => 'Brwi i rzęsy', 'imageUrl' => 'https://i.gyazo.com/a4ff013ac68b243a01ef5bafb7665360.png'],
            ['name' => 'Makijaż', 'imageUrl' => 'https://i.gyazo.com/95e0e680712f2ade8d861dff73d420b3.png'],
            ['name' => 'Depilacja', 'imageUrl' => 'https://i.gyazo.com/4453a39178210293f1414ccf6ad702db.png'],
        ];

        foreach ($categories as $categoryData) {
            $category = new ServiceCategory();
            $category->setName($categoryData['name']);
            $category->setImageUrl($categoryData['imageUrl']);
            $manager->persist($category);
        }

        $manager->flush();
    }
}