<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\{Room, Section, Package, Child};


class DemoSeed extends Seeder {
    public function run(): void {
        $room1 = Room::firstOrCreate(['name' => 'Комната 1']);
        $room2 = Room::firstOrCreate(['name' => 'Комната 2']);


        $art = Section::firstOrCreate(['name'=>'Творчество'], ['room_id'=>$room1->id]);
        $drawing = Section::firstOrCreate(['name'=>'Рисование','parent_id'=>$art->id,'room_id'=>$room1->id]);
        $dance = Section::firstOrCreate(['name'=>'Танцы','room_id'=>$room2->id]);


        Package::firstOrCreate(['section_id'=>$drawing->id,'type'=>'visits','visits_count'=>8], ['price'=>20000]);
        Package::firstOrCreate(['section_id'=>$drawing->id,'type'=>'period','days'=>30], ['price'=>30000]);
        Package::firstOrCreate(['section_id'=>$dance->id,'type'=>'visits','visits_count'=>12], ['price'=>36000]);


        Child::firstOrCreate(['first_name'=>'Али','last_name'=>'Ибраев']);
        Child::firstOrCreate(['first_name'=>'Мия','last_name'=>'Т.']);
    }
}
