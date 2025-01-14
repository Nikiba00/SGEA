<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;


class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Cambiamos create por firstOrCreate
        $rol1 =Role::firstOrCreate(['name'=>'Administrador']);
        $rol2 =Role::firstOrCreate(['name'=>'Comite']);
        $rol3 =Role::firstOrCreate(['name'=>'Autor']);
        $rol4 =Role::firstOrCreate(['name'=>'Revisor']);
        $rol5 =Role::firstOrCreate(['name'=>'Invitado']);

        Permission::firstOrCreate(['name'=>'areas.index'])->syncRoles([$rol1]);
        Permission::firstOrCreate(['name'=>'areas.create'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'areas.edit'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'areas.read'])->syncRoles([$rol1,$rol2,$rol3,$rol4,$rol5]);
        Permission::firstOrCreate(['name'=>'areas.destroy'])->syncRoles([$rol1,$rol2]);

        Permission::firstOrCreate(['name'=>'eventos.index'])->syncRoles([$rol1,$rol2,$rol3,$rol4,$rol5]);
        Permission::firstOrCreate(['name'=>'eventos.create'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'eventos.edit'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'eventos.read'])->syncRoles([$rol1,$rol2,$rol3,$rol4,$rol5]);
        Permission::firstOrCreate(['name'=>'eventos.destroy'])->syncRoles([$rol1,$rol2]);
        
        Permission::firstOrCreate(['name'=>'usuarios.index'])->syncRoles([$rol1]);
        Permission::firstOrCreate(['name'=>'usuarios.create'])->syncRoles([$rol1,$rol2,$rol3,$rol5]);
        Permission::firstOrCreate(['name'=>'usuarios.edit'])->syncRoles([$rol1,$rol2,$rol3,$rol4,$rol5]);
        Permission::firstOrCreate(['name'=>'usuarios.read'])->syncRoles([$rol1,$rol2,$rol3,$rol4,$rol5]);
        Permission::firstOrCreate(['name'=>'usuarios.destroy'])->syncRoles([$rol1]);

        Permission::firstOrCreate(['name'=>'participantes.index'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'participantes.read'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'participantes.edit'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name' =>'participantes.create'])->syncRoles([$rol1,$rol2,$rol3,$rol5]);
        Permission::firstOrCreate(['name' =>'participantes.destroy'])->syncRoles([$rol1,$rol5]);

        
        Permission::firstOrCreate(['name'=>'articulos.index'])->syncRoles([$rol1,$rol2,$rol3]);
        Permission::firstOrCreate(['name'=>'articulos.create'])->syncRoles([$rol1,$rol2,$rol3,$rol5]);
        Permission::firstOrCreate(['name'=>'articulos.edit'])->syncRoles([$rol1,$rol2,$rol3,$rol5]);
        Permission::firstOrCreate(['name'=>'articulos.read'])->syncRoles([$rol1,$rol2,$rol3,$rol4,$rol5]);
        Permission::firstOrCreate(['name'=>'articulos.destroy'])->syncRoles([$rol1,$rol2,$rol3,$rol5]);

        Permission::firstOrCreate(['name'=>'articulos_autores.index'])->syncRoles([$rol1]);
        Permission::firstOrCreate(['name'=>'articulos_autores.create'])->syncRoles([$rol1,$rol2,$rol3,$rol5]);
        Permission::firstOrCreate(['name'=>'articulos_autores.edit'])->syncRoles([$rol1,$rol2,$rol3]);
        Permission::firstOrCreate(['name'=>'articulos_autores.read'])->syncRoles([$rol1,$rol2,$rol3,$rol4,$rol5]);
        Permission::firstOrCreate(['name'=>'articulos_autores.destroy'])->syncRoles([$rol1,$rol2,$rol3]);
        
        Permission::firstOrCreate(['name'=>'revisores_articulos.index'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'revisores_articulos.create'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'revisores_articulos.edit'])->syncRoles([$rol1,$rol2,$rol4]);
        Permission::firstOrCreate(['name'=>'revisores_articulos.read'])->syncRoles([$rol1,$rol2,$rol3,$rol4]);
        Permission::firstOrCreate(['name'=>'revisores_articulos.destroy'])->syncRoles([$rol1,$rol2]);

        Permission::firstOrCreate(['name'=>'reportes.index'])->syncRoles([$rol3]);
        Permission::firstOrCreate(['name'=>'reportes.create'])->syncRoles([$rol1,$rol2,$rol3]);
        Permission::firstOrCreate(['name'=>'reportes.edit'])->syncRoles([$rol1,$rol2,$rol3,$rol5]);
        Permission::firstOrCreate(['name'=>'reportes.read'])->syncRoles([$rol1,$rol2,$rol3,$rol4,$rol5]);
        Permission::firstOrCreate(['name'=>'reportes.destroy'])->syncRoles([$rol1,$rol2,$rol3,$rol5]);
        Permission::firstOrCreate(['name'=>'reportes.download'])->syncRoles([$rol1,$rol2,$rol3]);

        Permission::firstOrCreate(['name'=>'agendas.index'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'agendas.create'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'agendas.edit'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'agendas.read'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'agendas.destroy'])->syncRoles([$rol1,$rol2]);
        Permission::firstOrCreate(['name'=>'agendas.download'])->syncRoles([$rol1,$rol2]);

        Permission::firstOrCreate(['name'=>'cesionDerechos.create'])->syncRoles([$rol3]);
        Permission::firstOrCreate(['name'=>'cesionDerechos.download'])->syncRoles([$rol3]);

        Permission::firstOrCreate(['name'=>'pagos.create'])->syncRoles([$rol2,$rol3,$rol4]);
        Permission::firstOrCreate(['name'=>'pagos.download'])->syncRoles([$rol2,$rol3,$rol4]);


    }
}
