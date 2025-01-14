<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\usuarios;

class UsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /*$user = usuarios::create([
            'foto' => 'DefaultH.jpg',
            'nombre'=>'SGEA',
            'ap_paterno'=>'',
            'ap_materno'=>'',
            'curp'=>'XEXX010101HNEXXXA4',
            'email'=>'sgea@toluca.tecnm.mx',
            'password'=> Hash::make('123'),
            'telefono'=>'0123456789',
            'estado'=>'alta,registrado'
        ]);
        $user2 = usuarios::create([
            'foto' => 'DefaultH.jpg',
            'nombre'=>'Luis Eduardo',
            'ap_paterno'=>'Gallegos',
            'ap_materno'=>'Garcia',
            'curp'=>'LGGE000502HMCSSXB6',
            'email'=>'lgallegosg@toluca.tecnm.mx',
            'password'=> Hash::make('123'),
            'telefono'=>'7292451298',
            'estado'=>'alta,registrado'
        ]);*/
        $user = usuarios::updateOrCreate(
            ['curp'=>'XEXX010101HNEXXXA4'],
            [
            'foto' => 'DefaultH.jpg',
            'nombre'=>'SGEA',
            'ap_paterno'=>'',
            'ap_materno'=>'',
            'email'=>'sgea@toluca.tecnm.mx',
            'password'=> Hash::make('123'),
            'telefono'=>'0123456789',
            'estado'=>'alta,registrado'
        ]);
        $user2 = usuarios::updateOrCreate(
             ['curp'=>'LGGE000502HMCSSXB6'],
            [
            'foto' => 'DefaultH.jpg',
            'nombre'=>'Luis Eduardo',
            'ap_paterno'=>'Gallegos',
            'ap_materno'=>'Garcia',
           
            'email'=>'lgallegosg@toluca.tecnm.mx',
            'password'=> Hash::make('123'),
            'telefono'=>'7292451298',
            'estado'=>'alta,registrado'
        ]);
        $user3 = usuarios::updateOrCreate(//AUTOR - REVISOR
            ['curp'=>'000000000000000000'],
            [
            'foto' => 'DefaultM.jpg',
            'nombre'=>'Adriana',
            'ap_paterno'=>'Reyes',
            'ap_materno'=>'Nava',
            
            'email'=>'tagejah114@suggets.com',
            'password'=> Hash::make('123'),
            'telefono'=>'0000000000',
            'estado'=>'alta,registrado'
        ]);
        $user4 = usuarios::updateOrCreate(//COMITE
            ['curp'=>'000000000000000001'],
            [
            'foto' => 'DefaultM.jpg',
            'nombre'=>'Ana Margarita',
            'ap_paterno'=>'Montiel',
            'ap_materno'=>'Leyva',
            
            'email'=>'sonia95_369@vuket.org',
            'password'=> Hash::make('123'),
            'telefono'=>'0000000001',
            'estado'=>'alta,registrado'
        ]);
        $user5 = usuarios::updateOrCreate(//REVISOR - COMITE
            ['curp'=>'000000000000000002'],
            [
            'foto' => 'DefaultH.jpg',
            'nombre'=>'Alla Antonio',
            'ap_paterno'=>'Flores',
            'ap_materno'=>'Fuentes',
            
            'email'=>'tusputeydu@gufum.com',
            'password'=> Hash::make('123'),
            'telefono'=>'0000000002',
            'estado'=>'alta,registrado'
        ]);
        $user6 = usuarios::updateOrCreate(//AUTOR
            ['curp'=>'000000000000000003'],
            [
            'foto' => 'DefaultM.jpg',
            'nombre'=>'Bany Sabel',
            'ap_paterno'=>'Hernandez',
            'ap_materno'=>'Cardona',
            
            'email'=>'7c5fea1caa@emailawb.pro',
            'password'=> Hash::make('123'),
            'telefono'=>'0000000003',
            'estado'=>'alta,registrado'
        ]);
        $user7 = usuarios::updateOrCreate(//COMITE - AUTOR
            ['curp'=>'000000000000000004'],
            [
            'foto' => 'DefaultM.jpg',
            'nombre'=>'Beatriz',
            'ap_paterno'=>'García',
            'ap_materno'=>'Gaitan',
            
            'email'=>'7c5fea1ca@emailawb.pro',
            'password'=> Hash::make('123'),
            'telefono'=>'0000000004',
            'estado'=>'alta,registrado'
        ]);
        $user8 = usuarios::updateOrCreate(//COMITE - AUTOR - REVISOR
            ['curp'=>'000000000000000005'],
            [
            'foto' => 'DefaultH.jpg',
            'nombre'=>'Drew',
            'ap_paterno'=>'Brees',
            'ap_materno'=>'Christopher',
            
            'email'=>'jisok30016@gholar.com',
            'password'=> Hash::make('123'),
            'telefono'=>'0000000005',
            'estado'=>'alta,registrado'
        ]);
        $user9 = usuarios::updateOrCreate(//AUTOR
            ['curp'=>'000000000000000006'],
            [
            'foto' => 'DefaultH.jpg',
            'nombre'=>'Alvin',
            'ap_paterno'=>'Kamara',
            'ap_materno'=>'Mentian',
            
            'email'=>'juancarlos4_139@vuket.org',
            'password'=> Hash::make('123'),
            'telefono'=>'0000000006',
            'estado'=>'alta,registrado'
        ]);
        $user10 = usuarios::updateOrCreate(//AUTOR - REVISOR
            ['curp'=>'000000000000000007'],
            [
            'foto' => 'DefaultH.jpg',
            'nombre'=>'Juan',
            'ap_paterno'=>'Carlos',
            'ap_materno'=>'Bodoque',
            
            'email'=>'48aufvf6oq@dygovil.com',
            'password'=> Hash::make('123'),
            'telefono'=>'0000000007',
            'estado'=>'alta,registrado'
        ]);
        $user11 = usuarios::updateOrCreate(//AUTOR - REVISOR
            ['curp'=>'000000000000000008'],
            [
            'foto' => 'DefaultH.jpg',
            'nombre'=>'Ian',
            'ap_paterno'=>'Blas',
            'ap_materno'=>'Aubert',
            
            'email'=>'deus.infortuni@gmail.com',
            'password'=> Hash::make('123'),
            'telefono'=>'0000000008',
            'estado'=>'alta,registrado'
        ]);
        
        $user->assignRole(1);
        $user2->assignRole(5);
        $user3->assignRole(4);
        $user4->assignRole(4);
        $user5->assignRole(4);
        $user6->assignRole(4);
        $user7->assignRole(4);
        $user8->assignRole(4);
        $user9->assignRole(4);
        $user10->assignRole(4);
        $user11->assignRole(4);
    }
}
